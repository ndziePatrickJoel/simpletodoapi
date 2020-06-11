<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\TodoList;
use App\Form\ItemFormType;
use Nelmio\ApiDocBundle\Annotation\Model;
use Psr\Log\LoggerInterface;
use Swagger\Annotations as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

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
    ) {
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->security = $security;
    }

    /**
     * @Route("/api/items", name="create_or_update_an_item", methods="PUT")
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

        try 
        {
        $data = json_decode($request->getContent(), true);
        $existingItem = array_key_exists('id', $data) ?
        $existingItem = $this->getDoctrine()->getManager()->getRepository(Item::class)->find($data['id']) :
        null;
        $item = $existingItem ? $existingItem : new Item();

        $todoList = $item->getTodoList();

        if (Item::COMPLETED_STATE == $item->getState()) {
            return new JsonResponse(
                [
                    'error' => 'Unauthaurized operation',
                    'errorDescription' => "Can update a COMPLETED item",
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        if ((Item::CREATED_STATE == $item->getState() && Item::COMPLETED_STATE == $data['state']) || (Item::PENDING_STATE == $item->getState() && Item::CREATED_STATE == $data['state'])) {
            return new JsonResponse(
                [
                    'error' => 'Unauthaurized operation',
                    'errorDescription' => "Can not transition from CREATED to COMPLETED",
                ],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $form = $this->createForm(ItemFormType::class, $item);
        $form->submit($data);

        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return new JsonResponse(
                [
                    'error' => 'Bad request',
                    'errorDescription' => "Validation failed with the following message " . json_encode($errors),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $item = $form->getData();
        $em = $this->getDoctrine()->getManager();
        $this->logger->info("Item ID is " . $item->getId());

        if (!$item->getId()) {
            $em->persist($item);
        }

        if (null != $todoList) {
            $item->setTodoList($todoList);
        }

        $em->flush();

        $jsonObject = $this->serializer->serialize($item, 'json', ['circular_reference_handler' => function ($object) {
            return $object->getId();
        }]);

        return new Response(
            $jsonObject,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
        }
        catch(\Exception $ex)
        {
            $this->logger->critical($ex->getMessage()."::::".$ex->getTraceAsString());

            return new JsonResponse(
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
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
        $jsonObject = $this->serializer->serialize($items, 'json', ['circular_reference_handler' => function ($object) {
            return $object->getId();
        }]);

        return new Response(
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
        $item = $this->getDoctrine()->getManager()->getRepository(Item::class)->find($id);

        if (!$item) {
            return new JsonResponse(
                [
                    'error' => 'Object not found',
                    'errorDescription' => 'The object you try to get does not exists '
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $jsonObject = $this->serializer->serialize($item, 'json', ['circular_reference_handler' => function ($object) {
            return $object->getId();
        }]);

        return new Response(
            $jsonObject,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }

}
