@extends('app')
@section('title','xiuno迁移')

@section('content')

    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">xiuno数据迁移</h3>
                </div>
                <div class="card-body">
                    @if(!$conf)
                        <form action="/admin/xiuno" method="POST">
                            <x-csrf/>
                            <div class="mb-3">
                                <label for="" class="form-label">xiuno程序目录</label>
                                <input type="text" class="form-control" name="path" placeholder="xiuno根目录">
                            </div>
                            <button class="btn btn-primary">保存</button>
                        </form>
                    @else
                    <div class="markdown">
                        配置成功!
                        <p>接下来，打开终端(ssh管理工具)</p>
                        <p>运行以下命令进入SForum根目录：</p>
                        <pre> <code class="language-bash">cd {{BASE_PATH}}</code></pre>
                        <p>然后运行以下命令开始数据迁移</p>
                        <pre> <code class="language-bash">php CodeFec plugin:xiuno</code></pre>
                        <p> 遇到问题来这里反馈: <a target="_blank" href="https://www.runpod.cn">https://www.runpod.cn</a></p>
                    </div>
                    @endif
                </div>
                <div class="card-footer">
                    交流论坛: <a target="_blank" href="https://www.runpod.cn">https://www.runpod.cn</a>
                </div>
            </div>
        </div>
    </div>

@endsection