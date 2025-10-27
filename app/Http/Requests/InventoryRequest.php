<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $inventoryId = $this->route('inventory');
        $method = $this->method();
        
        $baseRules = [
            'name' => [
                $method === 'PUT' ? 'required' : 'sometimes',
                'string', 
                'max:255', 
                'regex:/^[a-zA-Z0-9\s\-\.]+$/' // Alphanumeric with spaces, hyphens, dots
            ],
            'sku' => [
                $method === 'PUT' ? 'required' : 'sometimes',
                'string', 
                'max:100', 
                'regex:/^[A-Z0-9\-]+$/', // Uppercase, numbers, hyphens
                'unique:inventories,sku,' . ($inventoryId ? $inventoryId->id : 'NULL')
            ],
            'quantity' => [
                $method === 'PUT' ? 'required' : 'sometimes',
                'integer', 
                'min:0', 
                'max:10000' // Prevent unreasonably large inventory
            ],
            'price' => [
                $method === 'PUT' ? 'required' : 'sometimes',
                'numeric', 
                'min:0', 
                'max:1000000', // Reasonable price cap
                'regex:/^\d+(\.\d{1,2})?$/' // Allows up to 2 decimal places
            ],
        ];

        return $baseRules;
    }

    // Custom error messages
    public function messages(): array
    {
        return [
            'name.regex' => 'Product name can only contain letters, numbers, spaces, hyphens, and dots.',
            'sku.regex' => 'SKU must be uppercase letters, numbers, and hyphens only.',
            'quantity.max' => 'Inventory quantity cannot exceed 10,000 units.',
            'price.max' => 'Price cannot exceed $1,000,000.',
            'price.regex' => 'Price must be a valid number with up to 2 decimal places.',
        ];
    }
}
