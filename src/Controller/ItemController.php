<?php

namespace App\Controller;


use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Country;
use App\Entity\TodoList;
use App\Entity\Item;
use App\Form\ItemFormType;
use Symfony\Component\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Security\Core\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;


class ItemController extends AbstractController
{
    private $omService;
    private $logger;
    private $translator;
    private $serializer;
    private $utils;
    private $security;


    public function __construct( 
    LoggerInterface $logger,
    Security $security,
    SerializerInterface $serializer
    )
    {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->security = $security;
    }

    /**
     * @Route("/api/todolist/{id}/items", name="create_or_update_an_item", methods="PUT")
     * @SWG\Response(
     *     response=200,
     *     description="The created item",
     *     @SWG\Schema(
     *             @SWG\Property(property="code", type="integer"),
     *             @SWG\Property(property="messages",
     *                ref=@Model(type=Item::class, groups={"full"})
     *             )
     *        )
     * )
     * 
     * @SWG\Tag(name="Item")
     */
    public function create(Request $request)
    {
        
        $data = json_decode($request->getContent());

        $id = $data["id"];

        $existingItem = $this->getDoctrine()->getManager()->getRepository(Item::class)->find($id);

        $item = $existingItem ? $existingItem : new Item();

        $form = $this->createForm(ItemFormType::class, $item);
        $form->submit($data);

        if(!$form->isValid())
        {
            $errors =[];
            foreach ($form->getErrors(true) as $error) 
            {
                $errors[] = $error->getMessage();
            }
            return JsonResponse::create(
                [
                    'error' => 'Bad request',
                    'errorDescription' => "Validation failed with the following message ".json_encode($errors)
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $item = $form->getData(); 
        $em = $this->getDoctrine()->getManager();

        if(!$item->getId())
        {
            $item->setUser($this->security->getUser());
            $em->persist($item);
        }
        
        $em->flush();

        $jsonObject = $this->serializer->serialize($item, 'json', ['circular_reference_handler' => function($object){
            return $object->getId();
        }]);

        return Response::create(
            $jsonObject,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }

    /**
     * @Route("/api/todolist/{id}/items/search/{offset}/{limit}", name="search_todo_list_items", methods="GET")
     * @SWG\Response(
     *     response=200,
     *     description="Search TodoList",
     *     @SWG\Schema(
     *         @SWG\Property(property="totalResults", type="integer"),
     *         @SWG\Property(property="limit", type="integer"),
     *         @SWG\Property(property="offset", type="integer"),
     *        @SWG\Property(property="data", 
     *          type="array",
     *         @SWG\Items(ref=@Model(type=Item::class, groups={"full"}))
     *     ))
     * )
     * 
     * @SWG\Parameter(
     *     name="query",
     *     in="query",
     *     required=false,
     *     type="string",
     *     description="The search query"
     * )
     * @SWG\Tag(name="Item")
     */
    public function search(Request $request, $id, int $offset = 1, int $limit = 10)
    {
        $query = $request->query->get('query');

        $items = $this->getDoctrine()->getManager()->getRepository(Item::class)->search($query, $offset, $limit, $id);
        $jsonObject = $this->serializer->serialize($items, 'json', ['circular_reference_handler' => function($object){
            return $object->getId();
        }]);

        return Response::create(
            $jsonObject,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        ); 
    }

    /**
     * @Route("/api/items/{id}", name="get_an_item", methods="GET")
     * @SWG\Response(
     *     response=200,
     *     description="The created todoList",
     *     
     * )
     * @SWG\Tag(name="Item")
     */
    public function getItem($id)
    {
        $item  = $this->getDoctrine()->getManager()->getRepository(Item::class)->find($id);

        if(!$item)
        {
            throw new NotFoundHttpException("The object you try to get does not exists");
        }

        $jsonObject = $this->serializer->serialize($item, 'json', ['circular_reference_handler' => function($object){
            return $object->getId();
        }]);

        return Response::create(
            $jsonObject,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        ); 
    } 

}