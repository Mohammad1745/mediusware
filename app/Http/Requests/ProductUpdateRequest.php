<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProductUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|min:3|max:50',
            'description' => 'required|min:10',
            'sku' => 'required|min:3|max:50',
            'product_variant' => 'required|array',
            'product_variant_prices' => 'required|array',
        ];
    }

    /**
     * @param Validator $validator
     * @throws ValidationException
     */
    protected function failedValidation (Validator $validator) {
        $errors = '';
        if ($validator->fails()) {
            $e = $validator->errors()->all();
            foreach ($e as $error) {
                $errors = $errors . $error . "\n";
            }
        }
        $json = [
            'success' => false,
            'message' => $errors,
            'data' => []
        ];
        $response = new JsonResponse( $json, 200);

        throw (new ValidationException( $validator, $response))
            ->errorBag($this->errorBag)->redirectTo($this->getRedirectUrl());
    }
}
