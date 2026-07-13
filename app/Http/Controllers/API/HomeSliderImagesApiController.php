<?php

namespace App\Http\Controllers\API;

use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HomeSliderImagesApiController extends Controller
{
    public function index(){

        $sliders = Slider::with('category.translations', 'product.translations')
            ->where(function ($query) {
                $query->where('media_type', 'image')->orWhereNull('media_type');
            })
            ->orderBy('id','DESC')
            ->get()
            ->map(function ($slider) {
                // Convert slider to array to ensure all data is included
                $sliderArray = $slider->toArray();
                
                // Ensure translations are included as arrays (not accessors)
                if ($slider->category) {
                    // Get category as array
                    $categoryArray = $slider->category->toArray();
                    // Hide the translations accessor that returns only current language
                    $slider->category->makeHidden(['translations']);
                    // Add full translations array from relation
                    if ($slider->category->relationLoaded('translations')) {
                        $categoryArray['translations'] = $slider->category->getRelation('translations')->toArray();
                    } else {
                        $categoryArray['translations'] = [];
                    }
                    // Update the category in slider array
                    $sliderArray['category'] = $categoryArray;
                }
                
                if ($slider->product) {
                    // Get product as array
                    $productArray = $slider->product->toArray();
                    // Hide the translations accessor that returns only current language
                    $slider->product->makeHidden(['translations']);
                    // Add full translations array from relation
                    if ($slider->product->relationLoaded('translations')) {
                        $productArray['translations'] = $slider->product->getRelation('translations')->toArray();
                    } else {
                        $productArray['translations'] = [];
                    }
                    // Update the product in slider array
                    $sliderArray['product'] = $productArray;
                }
                
                return $sliderArray;
            })
            ->toArray();

        return CommonHelper::responseWithData($sliders);
    }

    public function save(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'type_id' => 'required_if:type,category,product',
            'slider_url' => 'required_if:type,==,slider_url',
            'image' => 'required|mimes:jpeg,jpg,png,gif'
        ],[
            'type_id.required_if' => 'The '.$request->type.' field is required when type is '.$request->type.'.',
            'slider_url.required_if' => 'The link field is required when type is Slider Url.',
            'slider_url.url' => 'The link must be a valid URL.',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $slider = new Slider();
        $slider->type = $request->type;
        $slider->type_id = ($request->type_id)?$request->type_id:0;
        $image = '';
        if($request->hasFile('image')){
            $file = $request->file('image');
            $fileName = time().'_'.rand(1111,99999).'.'.$file->getClientOriginalExtension();
            $image = Storage::disk('public')->putFileAs('sliders', $file, $fileName);
        }
       $slider->image = $image;
       $slider->media_type = 'image';
       $slider->slider_url = $request->slider_url;
       $slider->save();
       return CommonHelper::responseSuccess('Home slider images saved successfully');
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(),[
            'type' => 'required',
            'type_id' => 'required_if:type,category,product',
            'slider_url' => 'required_if:type,==,slider_url',
            'image' => $request->hasFile('image') ? 'mimes:jpeg,jpg,png,gif' : ''
        ],[
            'type_id.required_if' => 'The '.$request->type.' field is required when type is '.$request->type.'.',
            'slider_url.required_if' => 'The link field is required when type is Slider Url.',
            'slider_url.url' => 'The link must be a valid URL.',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        if(isset($request->id)){

            $slider = Slider::find($request->id);
            $slider->type = $request->type;
            $slider->type_id = $request->type_id;
            $slider->status = $request->status;
            if($request->hasFile('image')){
                @Storage::disk('public')->delete($slider->image);
                $file = $request->file('image');
                $fileName = time().'_'.rand(1111,99999).'.'.$file->getClientOriginalExtension();
                $image = Storage::disk('public')->putFileAs('sliders', $file, $fileName);
                $slider->image = $image;
            }
            $slider->slider_url = $request->slider_url;
            $slider->media_type = 'image';
            $slider->save();
        }


        return CommonHelper::responseSuccess('Slider updated successfully');
    }

    public function videos()
    {
        $videos = Slider::where('media_type', 'video')
            ->orderBy('id', 'DESC')
            ->get();

        return CommonHelper::responseWithData($videos);
    }

    public function activeVideos()
    {
        $videos = Slider::where('media_type', 'video')
            ->where('display_location', request()->get('display_location', 'hero_section'))
            ->where('status', 1)
            ->orderBy('id', 'DESC')
            ->get()
            ->makeHidden(['image', 'created_at', 'updated_at'])
            ->map(function ($video) {
                $video->type_id = $video->type_id ? intval($video->type_id) : 0;
                $video->slider_url = $video->slider_url ?? "";
                return $video;
            })
            ->values();

        return CommonHelper::responseWithData($videos);
    }

    public function saveVideo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'display_location' => 'required|string|in:hero_section,home_page,category_page,product_page',
            'status' => 'required|in:0,1',
            'video' => 'required|file|mimes:mp4,webm,ogg|max:20480',
        ], [
            'video.mimes' => 'Hero slider video must be MP4, WEBM, or OGG.',
            'video.max' => 'Hero slider video must be 20 MB or smaller.',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $slider = new Slider();
        $slider->name = $request->name;
        $slider->type = 'video';
        $slider->type_id = 0;
        $slider->media_type = 'video';
        $slider->display_location = $request->display_location;
        $slider->status = $request->status;

        $file = $request->file('video');
        $fileName = time() . '_' . rand(1111, 99999) . '.' . $file->getClientOriginalExtension();
        $slider->image = Storage::disk('public')->putFileAs('sliders/videos', $file, $fileName);
        $slider->slider_url = asset('storage/' . $slider->image);
        $slider->save();

        return CommonHelper::responseSuccess('Hero slider video saved successfully');
    }

    public function updateVideo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:sliders,id',
            'name' => 'required|string|max:255',
            'display_location' => 'required|string|in:hero_section,home_page,category_page,product_page',
            'status' => 'required|in:0,1',
            'video' => $request->hasFile('video') ? 'file|mimes:mp4,webm,ogg|max:20480' : '',
        ], [
            'video.mimes' => 'Hero slider video must be MP4, WEBM, or OGG.',
            'video.max' => 'Hero slider video must be 20 MB or smaller.',
        ]);

        if ($validator->fails()) {
            return CommonHelper::responseError($validator->errors()->first());
        }

        $slider = Slider::find($request->id);
        if (!$slider) {
            return CommonHelper::responseError('Video not found');
        }

        $slider->name = $request->name;
        $slider->type = 'video';
        $slider->type_id = 0;
        $slider->status = $request->status;
        $slider->media_type = 'video';
        $slider->display_location = $request->display_location;

        if ($request->hasFile('video')) {
            @Storage::disk('public')->delete($slider->image);
            $file = $request->file('video');
            $fileName = time() . '_' . rand(1111, 99999) . '.' . $file->getClientOriginalExtension();
            $slider->image = Storage::disk('public')->putFileAs('sliders/videos', $file, $fileName);
        }
        $slider->slider_url = $slider->image ? asset('storage/' . $slider->image) : '';

        $slider->save();

        return CommonHelper::responseSuccess('Hero slider video updated successfully');
    }

    public function delete(Request $request){

        if(isset($request->id)){

            $slider = Slider::find($request->id);
            if($slider){
                @Storage::disk('public')->delete($slider->image);
                $slider->delete();
                return CommonHelper::responseSuccess('slider deleted successfully');
            }else{
                return CommonHelper::responseSuccess('Slider already deleted');
            }
        }
    }


}
