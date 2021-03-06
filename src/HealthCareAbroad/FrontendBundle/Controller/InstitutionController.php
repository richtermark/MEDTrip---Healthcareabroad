<?php
/**
 *
 * @author Adelbert Silla
 * @author Richtermark Baay
 *
 */

namespace HealthCareAbroad\FrontendBundle\Controller;

use HealthCareAbroad\HelperBundle\Entity\GlobalAwardTypes;

use HealthCareAbroad\FrontendBundle\Services\FrontendMemcacheKeysHelper;

use HealthCareAbroad\ApiBundle\Services\InstitutionMedicalCenterApiService;

use HealthCareAbroad\MediaBundle\Services\ImageSizes;

use HealthCareAbroad\ApiBundle\Services\InstitutionApiService;

use HealthCareAbroad\HelperBundle\Services\PageMetaConfigurationService;

use HealthCareAbroad\HelperBundle\Entity\PageMetaConfiguration;

use HealthCareAbroad\InstitutionBundle\Entity\InstitutionMedicalCenterStatus;

use Doctrine\ORM\Query\Expr\Join;

use HealthCareAbroad\InstitutionBundle\Entity\InstitutionInquiry;

use HealthCareAbroad\FrontendBundle\Form\InstitutionInquiryFormType;

use HealthCareAbroad\InstitutionBundle\Entity\Institution;

use HealthCareAbroad\InstitutionBundle\Entity\InstitutionPropertyType;

use HealthCareAbroad\MedicalProcedureBundle\Entity\MedicalCenter;

use HealthCareAbroad\InstitutionBundle\Entity\InstitutionMedicalCenterGroupStatus;

use HealthCareAbroad\InstitutionBundle\Entity\InstitutionStatus;

use HealthCareAbroad\AdminBundle\Entity\ErrorReport;

use HealthCareAbroad\HelperBundle\Form\ErrorReportFormType;
use HealthCareAbroad\HelperBundle\Event\CreateErrorReportEvent;
use HealthCareAbroad\HelperBundle\Event\ErrorReportEvent;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class InstitutionController extends ResponseHeadersController
{
    /**
     * @var array
     */
    protected $institution;
    
    /**
     * @var InstitutionApiService
     */
    private $apiInstitutionService;
    
    /**
     * @var InstitutionMedicalCenterApiService
     */
    private $apiInstitutionMedicalCenterService;

//     public function preExecute()
//     {
//         $request = $this->getRequest();
//         $this->apiInstitutionService = $this->get('services.api.institution');

//         if($slug = $request->get('institutionSlug')) {
//             $url = $this->generateUrl('api_institution_getBySlug', array('slug' => $slug), true);
            
// //             $guzzle = new \Guzzle\Service\Client();
// //             $response = $guzzle->get($url)->send();
// //             $institution = $response->getBody(true);
            
// //             $this->institution = file_get_contents($url);
// //             $response = $this->forward('ApiBundle:Institution:getBySlug', array('slug' => $slug));
            
//             $this->institution = $this->apiInstitutionService->findBySlug($slug);
//             //$this->institution = $this->get('services.institution')->getFullInstitutionBySlug($slug);

//             if(!$this->institution) {
//                 throw $this->createNotFoundException('Invalid institution');
//             }
//         }

//     }

    /**
     * 
     * @param Request $request
     * @return Response
     * @author acgvelarde
     */
    public function profileAction(Request $request)
    {
        $start = \microtime(true);
        $slug = $request->get('institutionSlug', null);
        $institutionId = $this->getDoctrine()->getRepository('InstitutionBundle:Institution')->getInstitutionIdBySlug($slug);
        if (!$institutionId) {
        	throw $this->createNotFoundException('Invalid institution');
        }

        $this->apiInstitutionService = $this->get('services.api.institution');
        $this->apiInstitutionMedicalCenterService = $this->get('services.api.institutionMedicalCenter');
        $memcacheService = $this->get('services.memcache');
        $memcacheKey = FrontendMemcacheKeysHelper::generateInsitutionProfileKey($institutionId);
        
        $cachedData = $memcacheService->get($memcacheKey);
        
        if (!$cachedData) {
            $mediaExtensionService = $this->apiInstitutionService->getMediaExtension();
            $this->institution = $this->apiInstitutionService->getInstitutionPublicDataById($institutionId);

            // process common data for both single and multiple center
            $this->apiInstitutionService
                ->buildGlobalAwards($this->institution) // build global awards data
                ->buildOfferedServices($this->institution) // build anciliary services data
                ->buildFeaturedMediaSource($this->institution) // build cover photo source
                ->buildLogoSource($this->institution) // build logo
                ->buildContactDetails($this->institution)
                ->buildExternalSites($this->institution)
            ;
            
            $isSingleCenterInstitution = $this->apiInstitutionService->isSingleCenterInstitutionType($this->institution['type']);
            if ($isSingleCenterInstitution) {
                // build view data for single center institution
                $this->processSingleCenterInstitution();
            } 
            else {
                // build view data for multiple center institution
                $this->processMultipleCenterInstitution();
            }
            
            $this->institution['specializationsList'] = $this->apiInstitutionService->listActiveSpecializations($this->institution['id']); 
            
            // cache this processed data
            $memcacheService->set($memcacheKey, $this->institution);
        }
        else {
            $this->institution = $cachedData;
            $isSingleCenterInstitution = $this->apiInstitutionService->isSingleCenterInstitutionType($this->institution['type']);
        }
        
        $firstMedicalCenter = isset($this->institution['institutionMedicalCenters'][0])
            ? $this->institution['institutionMedicalCenters'][0]
            : null;
        
        // set request variables to be used by page meta components
        $this->getRequest()->attributes->add(array(
            'institution' => $this->institution,
            'pageMetaContext' => PageMetaConfiguration::PAGE_TYPE_INSTITUTION,
            'pageMetaVariables' => array(
                PageMetaConfigurationService::ACTIVE_CLINICS_COUNT_VARIABLE => \count($this->institution['institutionMedicalCenters']),
                PageMetaConfigurationService::SPECIALIZATIONS_COUNT_VARIABLE => \count($this->institution['specializationsList']),
                // get the first 10 as list
                PageMetaConfigurationService::SPECIALIZATIONS_LIST_VARIABLE => \implode(', ',  \array_slice($this->institution['specializationsList'],0, 10, true))
        )));

        $params = array(
            'institution' => $this->institution,
            'isSingleCenterInstitution' => $isSingleCenterInstitution,
            'institutionDoctors' => $this->institution['doctors'],
            'institutionMedicalCenter' => $firstMedicalCenter, // will only be used in single center 
            'form' => $this->createForm(new InstitutionInquiryFormType(), new InstitutionInquiry())->createView(),
            'institutionAwards' => $this->institution['globalAwards'],
            'institutionServices' => $this->institution['offeredServices'],
            'awardTypes' => GlobalAwardTypes::getTypes(),
            'photos' => $this->get('services.institution.gallery')->getInstitutionPhotos($this->institution['id']),
        );        
        
        $content = $this->render('FrontendBundle:Institution:profile.html.twig', $params);
        $end = \microtime(true); $diff = $end-$start;
        //echo "{$diff}s"; exit;
        $response= $this->setResponseHeaders($content);
        
        return $response;
    }
    
    private function processSingleCenterInstitution()
    {
        $firstMedicalCenter = isset($this->institution['institutionMedicalCenters'][0])
            ? $this->institution['institutionMedicalCenters'][0]
            : null;
        
        if (!$firstMedicalCenter) {
            // FIXME: no medical center, right now throw an exception since this should not happen
            throw $this->createNotFoundException('Invalid single center clinic');
        }
        
        $this->apiInstitutionMedicalCenterService
            ->buildInstitutionSpecializations($firstMedicalCenter)
            ->buildBusinessHours($firstMedicalCenter)
            ->buildDoctors($firstMedicalCenter)
        ;
        
        // build doctors from first clinic
        $this->institution['doctors'] = $firstMedicalCenter['doctors'];
        $this->institution['institutionMedicalCenters'][0] = $firstMedicalCenter;
    }
    
    private function processMultipleCenterInstitution()
    {
        // build doctors data
        $this->apiInstitutionService->buildDoctors($this->institution); 
        
        // Hesitant on modifying the twig extension since it is used in many contexts
        foreach ($this->institution['institutionMedicalCenters'] as $key => &$imcData) {
            $this->apiInstitutionMedicalCenterService
            ->buildLogoSource($imcData, ImageSizes::MINI, InstitutionMedicalCenterApiService::CONTEXT_HOSPITAL_CLINICS_LIST);
        
            // flatten specializations list for displaying list
            // do this here so we will have no processing in twig template and so this will be cached
            $imcData['specializationsList'] = array();
            foreach ($imcData['institutionSpecializations'] as $instSpecialization) {
                // we always assume this since this is eagerly loaded in buildInstitutionSpecializations
                $imcData['specializationsList'][$instSpecialization['specialization']['id']] = $instSpecialization['specialization']['name'];
            }
        }
    }

    public function errorReportAction()
    {

    }
}