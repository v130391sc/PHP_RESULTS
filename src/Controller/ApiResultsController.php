<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Result;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ApiResultsController
 *
 * @package App\Controller
 *
 * @Route(
 *     path=ApiResultsController::RUTA_API,
 *     name="api_results_"
 * )
 */
class ApiResultsController extends AbstractController
{
    public const RUTA_API = '/api/v1/results';

    /** @var EntityManagerInterface $entityManager */
    private $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * Summary: Returns all results
     * Notes: Returns all results from the system that the user has access to.
     *
     * @param   Request $request
     * @return  Response
     * @Route(
     *     ".{_format}",
     *     defaults={"_format": null},
     *     requirements={
     *         "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="cget"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="Invalid credentials."
     * )
     */
    public function cgetAction(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $results = $this->entityManager
                ->getRepository(Result::class)
                ->findAll();
        } elseif ($this->isGranted('ROLE_USER')){
            $results = $this->entityManager
                ->getRepository(Result::class)
                ->findBy([ 'user' => $this->getUser()->getId() ]);
        } else {
            throw new HttpException(   // 403
                Response::HTTP_FORBIDDEN,
                "`Forbidden`: you don't have permission to access"
            );
        }
        $format = Utils::getFormat($request);

        // No hay usuarios?
        if (empty($results)) {
            $message = new Message(Response::HTTP_NOT_FOUND, Response::$statusTexts[404]);
            return Utils::apiResponse(
                $message->getCode(),
                [ 'message' => $message ],
                $format
            );
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ 'result' => $results ],
            $format
        );
    }

    /**
     * Summary: Returns a result based on a single ID
     * Notes: Returns the result if the user has access to get it.
     *
     * @param Request $request
     * @param  int $resultId Result id
     * @return Response
     * @Route(
     *     "/{resultId}.{_format}",
     *     defaults={ "_format": null },
     *     requirements={
     *          "resultId": "\d+",
     *          "_format": "json|xml"
     *     },
     *     methods={ Request::METHOD_GET },
     *     name="get"
     * )
     *
     * @Security(
     *     expression="is_granted('IS_AUTHENTICATED_FULLY')",
     *     statusCode=401,
     *     message="Invalid credentials."
     * )
     */
    public function getAction(Request $request, int $resultId): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $result = $this->entityManager
                ->getRepository(Result::class)
                ->findOneBy(['id' => $resultId]);
        } elseif ($this->isGranted('ROLE_USER')) {
            $result = $this->entityManager
                ->getRepository(Result::class)
                ->findOneBy(['id' => $resultId]);
            if(!empty($result) && $result->getUser()->getId() !== $this->getUser()->getId()){
                $result = NULL;
            }
        } else {
            throw new HttpException(   // 403
                Response::HTTP_FORBIDDEN,
                "`Forbidden`: you don't have permission to access"
            );
        }
        $format = Utils::getFormat($request);

        if (empty($result)) {
            $message = new Message(Response::HTTP_NOT_FOUND, Response::$statusTexts[404]);
            return Utils::apiResponse(
                $message->getCode(),
                [ 'message' => $message ],
                $format
            );
        }

        return Utils::apiResponse(
            Response::HTTP_OK,
            [ 'result' => $result ],
            $format
        );
    }

}
