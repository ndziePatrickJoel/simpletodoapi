<?php

namespace App\Controller;


use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\TodoList;
use App\Form\TodoListFormType;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Security;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Model;


class TodoListController extends AbstractController
{
    private $logger;
    private $serializer;
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

        return new Response(
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

        if (TodoList::COMPLETED_STATE == $todoList->getState()) {
            return new JsonResponse(
                [
                    'error' => 'Unauthaurized operation',
                    'errorDescription' => "Can update a COMPLETED item",
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ((TodoList::CREATED_STATE == $todoList->getState() && TodoList::COMPLETED_STATE == $data['state']) || (TodoList::PENDING_STATE == $todoList->getState() && TodoList::CREATED_STATE == $data['state'])) {
            return new JsonResponse(
                [
                    'error' => 'Unauthaurized operation',
                    'errorDescription' => "Can not transition from CREATED to COMPLETED",
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $form = $this->createForm(TodoListFormType::class, $todoList);

        $form->submit($data);

        if(!$form->isValid())
        {
            $errors =[];
            foreach ($form->getErrors(true) as $error) 
            {
                $errors[] = $error->getMessage();
            }
            return new JsonResponse(
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

        return new Response(
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
            return new JsonResponse(
                [
                    'error' => 'Object not found',
                    'errorDescription' => 'The object you try to get does not exists '
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $jsonObject = $this->serializer->serialize($item, 'json', ['circular_reference_handler' => function($object){
            return $object->getId();
        }]);

        return new Response(
            $jsonObject,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        ); 
    }
}