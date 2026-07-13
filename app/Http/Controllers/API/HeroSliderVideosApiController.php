<?php

namespace App\Http\Controllers\API;

use App\Helpers\CommonHelper;
use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HeroSliderVideosApiController extends Controller
{
    public function index()
    {
        $videos = Slider::where(function ($query) {
                $query->where('media_type', 'video')
                    ->orWhere('type', 'video');
            })
            ->orderBy('id', 'DESC')
            ->get();

        return CommonHelper::responseWithData($videos, $videos->count());
    }

    public function activeVideos(Request $request)
    {
        $displayLocation = $request->get('display_location');

        $videos = Slider::where(function ($query) {
                $query->where('media_type', 'video')
                    ->orWhere('type', 'video');
            })
            ->when($displayLocation, function ($query) use ($displayLocation) {
                $query->where('display_location', $displayLocation);
            })
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

        return CommonHelper::responseWithData($videos, $videos->count());
    }

    public function save(Request $request)
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

    public function update(Request $request)
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

    public function delete(Request $request)
    {
        if (!isset($request->id)) {
            return CommonHelper::responseError('Video id is required');
        }

        $slider = Slider::find($request->id);
        if (!$slider) {
            return CommonHelper::responseSuccess('Video already deleted');
        }

        @Storage::disk('public')->delete($slider->image);
        $slider->delete();

        return CommonHelper::responseSuccess('Hero slider video deleted successfully');
    }
}
