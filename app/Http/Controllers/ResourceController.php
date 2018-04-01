<?php

namespace App\Http\Controllers;

use App\{
    MoviesBase, Resource, ResourceTypeDetails
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResourceController extends Controller
{

    /**
     * 编辑资源接口
     * @param $id
     * @param $rid
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function edit($id, $rid, Request $request)
    {
        try {
            $validator = $this->validator($request->all());
            if ($validator->fails()) {
                return response(['error' => $validator->errors()], 422);
            }
            // 检测该影片是否存在于数据库中
            if (!MoviesBase::where('id', $id)->first()) {
                throw new \Exception('影片信息不存在');
            };
            $data = $request->all();
            $resource = Resource::where([
                'movies_id' => $id,
                'resource_id' => $rid,
                'sharer' => $request->get('id-token')->uid
            ]);
            if (!$resource->get()->count()) {
                throw new \Exception('非法请求');
            }
            $type = ResourceTypeDetails::where('type_name', $data['type'])->first();
            if (!$type) {
                throw new \Exception('错误的资源类型', 422);
            }
            $type_id = $type->type_id;
            if ($resource->update([
                'resource_type' => $type_id,
                'title' => $data['title'],
                'password' => $data['password'],
                'url' => $data['url'],
                'instruction' => $data['instruction']
            ])) {
                return response([
                    'id' => $rid,
                    'type' => $data['type'],
                    'title' => $data['title'],
                    'password' => $data['password'],
                    'url' => $data['url'],
                    'instruction' => $data['instruction'],
                    'sharer' => [
                        'id' => $request->get('id-token')->uid,
                        'name' => $request->get('id-token')->uname
                    ]
                ]);
            }
        } catch (\Exception $e) {
            return response(['error' => '编辑资源失败：' . $e->getMessage()], 400);
        }
    }

    /**
     * 验证请求参数
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'type' => 'required',
            'title' => 'required',
            'url' => 'required',
            'instruction' => 'required',
        ]);
    }

    /**
     * 删除资源接口
     * @param $id
     * @param $rid
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete($id, $rid, Request $request)
    {
        try {
            // 检测该影片是否存在于数据库中
            if (!MoviesBase::where('id', $id)->first()) {
                throw new \Exception('影片信息不存在');
            };

            $resource = Resource::where(
                [
                    'movies_id' => $id,
                    'resource_id' => $rid,
                    'sharer' => $request->get('id-token')->uid
                ]);
            if (!$resource->get()->count()) {
                throw new \Exception('非法请求');
            }
            if ($resource->delete()) {
                return response([], 204);
            }
        } catch (\Exception $e) {
            return response(['error' => '删除资源失败：' . $e->getMessage()], 400);
        }
    }

    /**
     * 增加资源接口
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function add(Request $request, $id)
    {

        try {
            $validator = $this->validator($request->all());
            if ($validator->fails()) {
                throw new \Exception($validator->errors(), 422);
            }
            // 检测该影片是否存在于数据库中
            if (!MoviesBase::where('id', $id)->first()) {
                throw new \Exception('影片信息不存在');
            };
            $data = $request->all();
            $type_cn = $data['type'];
            $data['type'] = ResourceTypeDetails::where('type_name', $data['type'])->first();
            if (!$data['type']) {
                throw new \Exception('错误的资源类型', 422);
            }
            $data['type'] = $data['type']->type_id;
            $resource = $this->create($data, $id);

            if ($res = $resource->save()) {

                return response([
                    'id' => $resource->id,
                    'type' => $type_cn,
                    'title' => $data['title'],
                    'password' => $data['password'] ?? 'null',
                    'url' => $data['url'],
                    'instruction' => $data['instruction'],
                    'sharer' => [
                        'id' => $request->get('id-token')->uid,
                        'name' => $request->get('id-token')->uname
                    ]
                ]);
            } else {
                throw new \Exception('未知错误', 400);
            }
        } catch (\Exception $e) {
            return response(['error' => '添加资源失败：' . $e->getMessage()], 400);
        }

    }

    /**
     * 使用 create 方法创建资源
     * @param array $data
     * @param $id
     * @return mixed
     */
    protected function create(array $data, $id)
    {
        return Resource::create([
            'movies_id' => $id,
            'resource_type' => $data['type'],
            'title' => $data['title'],
            'password' => $data['password'],
            'url' => $data['url'],
            'instruction' => $data['instruction'],
            'sharer' => request()->get('id-token')->uid
        ]);
    }

    /**
     * 显示资源接口
     * @param $movie_id
     */
    public function showResources($movie_id)
    {

    }
}