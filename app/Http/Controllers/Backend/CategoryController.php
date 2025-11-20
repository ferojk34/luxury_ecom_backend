<?php

namespace App\Http\Controllers\Backend;

use App\Models\Category;
use App\Traits\GenerateSlug;
use Illuminate\Http\Request;
use App\Traits\HandlesImageUpload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CategoryController extends Controller
{
    use GenerateSlug, HandlesImageUpload;

    public function show(string $id)
    {
        try {
                $category = Category::findOrFail($id);
                return response()->json([
                    'success' => true,
                    'message' => 'Category details fetched successfully',
                    'data'    => $category,
                ]);

            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found.',
                ], 404);
            }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
                $validator = Validator::make($request->all(), [
                'parent_id'      => ['nullable', 'uuid', 'exists:categories,id'],
                'title'          => ['required', 'string', 'max:255'],
                'sort_order'     => ['nullable', 'integer', 'min:0'],
                'content'        => ['nullable', 'string'],
                'meta_title'     => ['nullable', 'string', 'max:255'],
                'meta_keywords'  => ['nullable', 'string', 'max:500'],
                'meta_desc'      => ['nullable', 'string'],
                'publish_status' => ['nullable', 'boolean'],

                'image' => [
                    'nullable',
                    'image',
                    'mimes:jpeg,jpg,png,webp',
                ],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();

            $slug = $this->generateSlug(Category::class, $validated['title']);

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $this->storeImage(
                    $request->file('image'),
                    'categories',
                    1200,
                );
            }

            $category = Category::create([
                'parent_id'      => $validated['parent_id'] ?? null,
                'title'          => $validated['title'],
                'slug'           => $slug,
                'image'          => $imagePath,
                'sort_order'     => $validated['sort_order'] ?? 0,
                'content'        => $validated['content'] ?? null,
                'meta_title'     => $validated['meta_title'] ?? null,
                'meta_keywords'  => $validated['meta_keywords'] ?? null,
                'meta_desc'      => $validated['meta_desc'] ?? null,
                'publish_status' => $validated['publish_status'] ?? 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data'    => $category,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error("category_store_error", [
                "error" => $e,
                "error_time" => now(),
            ]);
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.'
            ], 500);
        }
    }


    public function edit(string $id)
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Category fetched successfully for editing',
                'data'    => $category,
            ]);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);

        } catch (\Throwable $e) {
            Log::error("category_edit_error", [
                "error" => $e,
                "error_time" => now(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }


    public function update(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            $category = Category::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'parent_id'      => ['nullable', 'uuid', 'exists:categories,id'],
                'title'          => ['required', 'string', 'max:255'],
                'sort_order'     => ['nullable', 'integer', 'min:0'],
                'content'        => ['nullable', 'string'],
                'meta_title'     => ['nullable', 'string', 'max:255'],
                'meta_keywords'  => ['nullable', 'string', 'max:500'],
                'meta_desc'      => ['nullable', 'string'],
                'publish_status' => ['nullable', 'boolean'],
                'image'          => ['nullable','image','mimes:jpeg,jpg,png,webp'],
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();

            // Handle slug changes
            $slug = $category->title !== $validated['title']
                ? $this->generateSlug(Category::class, $validated['title'])
                : $category->slug;

            // Handle image replace
            $imagePath = $category->image;
            if ($request->hasFile('image')) {
                $this->deleteOldImage($category->image);
                $imagePath = $this->storeImage($request->file('image'), 'categories', 1200);
            }

            $category->update([
                'parent_id'      => $validated['parent_id'] ?? null,
                'title'          => $validated['title'],
                'slug'           => $slug,
                'image'          => $imagePath,
                'sort_order'     => $validated['sort_order'] ?? 0,
                'content'        => $validated['content'] ?? null,
                'meta_title'     => $validated['meta_title'] ?? null,
                'meta_keywords'  => $validated['meta_keywords'] ?? null,
                'meta_desc'      => $validated['meta_desc'] ?? null,
                'publish_status' => $validated['publish_status'] ?? 0,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data'    => $category,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);

        } catch (\Throwable $e) {
            Log::error("category_update_error", [
                "error" => $e,
                "error_time" => now(),
            ]);
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        DB::beginTransaction();

        try {
            $category = Category::findOrFail($id);

            // delete image
            $this->deleteOldImage($category->image);

            // delete DB record
            $category->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();
            Log::error("category_delete_error", [
                "error" => $e->getMessage(),
                "time"  => now(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }
}
