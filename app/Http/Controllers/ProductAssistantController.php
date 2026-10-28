<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductAssistantController extends Controller
{
    public function __invoke(Request $request, Product $product, ProductAssistant $assistant): JsonResponse
    {
        abort_unless($product->status === 'published', 404);

        $data = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        return response()->json($assistant->ask($product, $data['question']));
    }
}
