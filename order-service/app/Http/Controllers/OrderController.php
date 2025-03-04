<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MongoDB\Client;

class OrderController extends Controller
{

    protected $db;
    protected $collection;

    public function __construct()
    {
        $this->db = new Client(env('MONGO_URI'));
        $this->collection = $this->db->orders_services_db->orders;
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
            $order = $this->collection->findOne(['_id' => $objectId]);

            if (!$order) {
                return response()->json(['message' => 'Orden no encontrada'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['message' => 'Orden encontrada', 'order' => $order], Response::HTTP_OK);
        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
            // Error al convertir el ID a ObjectId (ID inválido)
            return response()->json(['message' => 'ID de la orden inválido'], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function store(Request $request)
    {
        // Obtener el token JWT del encabezado de autorización para poder llamar a otros servicios
        $token = $request->header('Authorization');
        $token = str_replace("Bearer ", "", $token);

        $valData = $this->validate($request, [
            'customer_name' => 'required|string|max:100',
            'items' => 'required|array',
            'items.*.product_id' => 'required|string',
            'items.*.name' => 'required|string|max:100',
            'items.*.description' => 'required|string|max:1000',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.category' => 'required|string|max:100',
            'items.*.available' => 'required|boolean',
            'items.*.ingredients' => 'required|array',
            'items.*.quantity' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',
            'status' => 'required|string|in:pending,completed,canceled',
        ]);

        $valData['created_at'] = new \MongoDB\BSON\UTCDateTime();
        $valData['updated_at'] = new \MongoDB\BSON\UTCDateTime();

        $updatedProducts = [];

        try {
            // Primero verificamos todos los productos
            foreach ($valData['items'] as $pro) {
                // Usar withHeaders para depurar la solicitud
                $inventoryResponse = Http::withToken($token)
                    ->timeout(600)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get(env('INVENTORY_SERVICE_URL') . '/products/' . $pro['product_id']);

                // Añadir depuración
                Log::info('Respuesta del servicio de inventario: ' . $inventoryResponse->body());

                if ($inventoryResponse->failed() || !$inventoryResponse->json()) {
                    return response()->json(['error' => 'Producto no encontrado: ' . $pro['product_id']], Response::HTTP_NOT_FOUND);
                }

                $product = $inventoryResponse->json();
                // Verificar si la respuesta incluye la clave 'producto' o si es el producto directamente
                if (isset($product['producto'])) {
                    $product = $product['producto'];
                }

                if ($product['quantity'] < $pro['quantity']) {
                    return response()->json(['error' => 'No hay suficiente stock para el producto: ' . $pro['name']], Response::HTTP_BAD_REQUEST);
                }

                $updatedProducts[] = [
                    'product_id' => $pro['product_id'],
                    'new_quantity' => $product['quantity'] - $pro['quantity']
                ];
            }

            // Luego actualizamos el inventario
            foreach ($updatedProducts as $productUpdate) {
                // CORREGIDO: Usar product_id del array productUpdate, no de $pro que está fuera de contexto
                $updatedResponse = Http::withToken($token)
                    ->timeout(600)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->put(env('INVENTORY_SERVICE_URL') . '/products/' . $productUpdate['product_id'], [
                        'quantity' => $productUpdate['new_quantity']
                    ]);

                if ($updatedResponse->failed()) {
                    Log::error('Error al actualizar inventario: ' . $updatedResponse->body());
                    return response()->json(['error' => 'Error al actualizar el inventario para el producto: ' . $productUpdate['product_id']], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // Finalmente guardamos la orden
            $this->collection->insertOne($valData);

            return response()->json(['message' => 'Orden guardada'], Response::HTTP_CREATED);
        } catch (Exception $e) {
            // Guardar log de error
            Log::error('Error al guardar la orden: ' . $e->getMessage());

            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function update(Request $request, string $id)
    {
        $validate = $this->validate($request, [
            'customer_name' => 'string|max:100',
            'items' => 'array',
            'total_price' => 'numeric|min:0',
            'status' => 'string|in:pending,completed,canceled',
        ]);

        $validate['updated_at'] = new \MongoDB\BSON\UTCDateTime();

        try {
            $order = $this->collection->updateOne(['_id' => new \MongoDB\BSON\ObjectId($id)], ['$set' => $validate]);

            if ($order->getMatchedCount() == 0) {
                return response()->json(['message' => 'Orden no encontrada'], Response::HTTP_NOT_FOUND);
            }

            return response()->json(['message' => 'Orden actualizada'], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destroy(string $id)
    {
        try {
            // Convertir el ID de string a ObjectId de MongoDB
            $objectId = new \MongoDB\BSON\ObjectId($id);

            // Eliminar la orden por su _id
            $order = $this->collection->deleteOne(['_id' => $objectId]);

            return response()->json(['message' => 'Orden eliminada'], Response::HTTP_OK);
        } catch (\MongoDB\Driver\Exception\InvalidArgumentException $e) {
            // Error al convertir el ID a ObjectId (ID inválido)
            return response()->json(['message' => 'ID de la orden inválido'], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
