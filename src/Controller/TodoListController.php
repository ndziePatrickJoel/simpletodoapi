<?php

namespace App\Controller;


use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Country;
use App\Entity\TodoList;
use App\Form\TodoListFormType;
use Symfony\Component\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Security\Core\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;




class TodoListController extends AbstractController
{
    private $omService;
    private $logger;
    private $translator;
    private $serializer;
    private $security;
    private $utils;


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
     * @Route("/api/todolist/search/{offset}/{limit}", name="search_todo_list", methods="GET")
     * @SWG\Response(
     *     response=200,
     *     description="Search TodoList",
     *     @SWG\Schema(
     *         type="array",
     *         @SWG\Items(ref=@Model(type=TodoList::class, groups={"full"}))
     *     )
     * )
     * 
     * @SWG\Parameter(
     *     name="query",
     *     in="query",
     *     required=false,
     *     type="string",
     *     description="The search query"
     * )
     * @SWG\Tag(name="TodoList")
     */
    public function search(Request $request, int $offset = 1, int $limit = 10)
    {
        $query = $request->query->get('query');

        $items = $this->getDoctrine()->getManager()->getRepository(TodoList::class)->search($query, $offset, $limit);

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
     * @Route("/api/todolist", name="create_a_todo_list", methods="PUT")
     * @SWG\Response(
     *     response=200,
     *     description="The created todoList",
     *     
     * )
     * @SWG\Tag(name="TodoList")
     */
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $existingTodoList = null;

        if(array_key_exists('id', $data))
        {
            $id = $data["id"];
            $existingTodoList = $this->getDoctrine()->getManager()->getRepository(TodoList::class)->find($id);
        }
        
        $todoList = $existingTodoList ? $existingTodoList : new TodoList();

        $form = $this->createForm(TodoListFormType::class, $todoList);

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

        $todoList = $form->getData(); 
        $todoList->setUser($this->security->getUser());
        $em = $this->getDoctrine()->getManager();

        if(!$todoList->getId())
        {
            $em->persist($todoList);
            foreach($todoList->getItems() as $item)
            {
                $item->setTodoList($todoList);
            }
        }

        $em->flush();

        $jsonObject = $this->serializer->serialize($todoList, 'json', ['circular_reference_handler' => function($object){
            return $object->getId();
        }]);

        return Response::create(
            $jsonObject,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }


    /**
     * @Route("/api/todolist/{id}", name="get_a_todo_list", methods="GET")
     * @SWG\Response(
     *     response=200,
     *     description="The created todoList",
     *     
     * )
     * @SWG\Tag(name="TodoList")
     */
    public function getTodoList(string $id)
    {
        $item  = $this->getDoctrine()->getManager()->getRepository(TodoList::class)->find($id);

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