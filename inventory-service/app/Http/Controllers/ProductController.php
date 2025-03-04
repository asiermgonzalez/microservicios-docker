<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MongoDB\Client;

class ProductController extends Controller
{

    protected $db;
    protected $collection;

    public function __construct()
    {
        $this->db = new Client(env('MONGO_URI'));
        $this->collection = $this->db->inventory->products;
    }


    public function index()
    {
        try {
            $products = $this->collection->find()->toArray();
            return response()->json($products, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function show(string $id)
    {
        try {
            // Convertir el ID de string a ObjectId de MongoDB
            $objectId = new \MongoDB\BSON\ObjectId($id);

            // Buscar el producto por su _id
            $product = $this->collection->findOne(['_id' => $objectId]);

            if (!$product) {
                return response()->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
            }

            return response()->json($product, Response::HTTP_OK);
        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
            // Error al convertir el ID a ObjectId (ID inválido)
            return response()->json(['message' => 'ID de producto inválido'], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function store(Request $request)
    {
        $validate = $this->validate($request, [
            'name' => 'required|string|max:100',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'category' => 'required|string|max:100',
            'available' => 'required|boolean',
            'ingredients' => 'required|array',
            'quantity' => 'required|integer|min:0',
        ]);

        try {
            // Comprobar si ya existe algún producto con el mismo nombre
            $exists = $this->collection->findOne(['name' => $validate['name']]);

            if ($exists) {
                return response()->json(['message' => 'El producto ya existe'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $product = $this->collection->insertOne($validate);
            return response()->json(['message' => 'Producto guardado'], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function update(Request $request, string $id)
    {
        $validate = $this->validate($request, [
            'name' => 'string|max:100',
            'description' => 'string|max:1000',
            'price' => 'numeric|min:0',
            'category' => 'string|max:100',
            'available' => 'boolean',
            'ingredients' => 'array',
            'quantity' => 'integer|min:0',
        ]);

        try {
            $product = $this->collection->updateOne(['_id' => new \MongoDB\BSON\ObjectId($id)], ['$set' => $validate]);

            if ($product->getMatchedCount() == 0) {
                return response()->json(['message' => 'Producto no encontrado'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['message' => 'Producto actualizado'], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destroy(string $id)
    {
        try {
            // Convertir el ID de string a ObjectId de MongoDB
            $objectId = new \MongoDB\BSON\ObjectId($id);

            // Eliminar el producto por su _id
            $product = $this->collection->deleteOne(['_id' => $objectId]);

            return response()->json(['message' => 'Producto eliminado'], Response::HTTP_OK);
        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
            // Error al convertir el ID a ObjectId (ID inválido)
            return response()->json(['message' => 'ID de producto inválido'], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function searchByName(Request $request)
    {
        $validate = $this->validate($request, [
            'name' => 'required|string|max:100',
        ]);

        try {
            $products = $this->collection->find(['name' => ['$regex' => '.*' . preg_quote($validate['name'], '/') . '.*', '$options' => 'i']])->toArray();

            if (empty($products)) return response()->json(['message' => 'no se encontraron productos con ese nombre'], Response::HTTP_NOT_FOUND);

            return response()->json(['message' => 'Productos encontrados', 'products' => $products], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
