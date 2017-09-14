<?php
/**上传多个
 * author: ran.ran
 * Date: 2015/12/9
 * Time: 10:11
 */

namespace yidashi\webuploader;

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\widgets\InputWidget;
use Yii;

class MultipeWebuploader extends InputWidget{
    //默认配置
    protected $_options;
    public $server;
    public function init()
    {
        parent::init();
        $this->options['number'] = isset($this->options['number']) ? $this->options['number'] : \Yii::$app->params['MaxNumber'];
        $this->options['boxId'] = isset($this->options['boxId']) ? $this->options['boxId'] : 'picker';
        $this->options['innerHTML'] = isset($this->options['innerHTML']) ? $this->options['innerHTML'] :'<button class="btn btn-primary">选择文件</button>';
        $this->options['previewWidth'] = isset($this->options['previewWidth']) ? $this->options['previewWidth'] : '250';
        $this->options['previewHeight'] = isset($this->options['previewHeight']) ? $this->options['previewHeight'] : '150';
    }
    public function run()
    {
        $this->registerClientJs();
        $value = Html::getAttributeValue($this->model, $this->attribute);
        $content = $this->options['innerHTML'];
        $filelist = '';
        if(empty($value)) {
            $filelist = Html::tag('ul','',['class'=>'filelist']);
        } else {
            //查询数据
            $li_list = '';
            $valueArr = explode(',', $value);
            foreach ($valueArr as $key=>$val) {
                $img_url = Yii::$app->params['UpFileData']::showImage($val);
                $img = strpos($img_url, 'http:') === false ? (\Yii::getAlias('@static') . '/' . $img_url) : $img_url;
                $li_items ='';
                $li_items .= '<li dataid="'.$val.'" id="WU_FILE_'.$key.'">';
                $li_items .='<p class="title">1.jpg</p>';
                $li_items .='<p class="imgWrap">';
                $li_items .='<img src="'.$img.'" width="'.$this->options['previewWidth'].'" height="'.$this->options['previewHeight'].'"></p>';
                $li_items .='<p class="progress"><span></span></p>';
                $li_items .='<div class="file-panel"><span data-id="'.$val.'" class="removeItems cancel">删除</span>';
                //$li_items .='<span data-id="'.$val.'" class="rotateRight">向右旋转</span>';
                //$li_items .='<span data-id="'.$val.'" class="rotateLeft">向左旋转</span>';
                $li_items .='<span data-id="'.$val.'" class="moveRight">向右移动</span>';
                $li_items .='<span data-id="'.$val.'" class="moveLeft">向左移动</span>';
                $li_items .='</div></li>';
                        
                
                $li_list .=$li_items;
            }
            
            $filelist = Html::tag('ul',$li_list,['class'=>'filelist']);
        }
        if($this->hasModel()){
           return Html::tag('div',$filelist.Html::tag('div', $content, ['id'=>$this->options['boxId']]) . Html::activeHiddenInput($this->model, $this->attribute),['id'=>'uploader']);
        }else{
            return Html::tag('div',$filelist.Html::tag('div', $content, ['id'=>$this->options['boxId']]) . Html::hiddenInput($this->name, $this->value),['id'=>'uploader']);
        }
    }

    /**
     * 注册js
     */
    private function registerClientJs()
    {
        WebuploaderAsset::register($this->view);
        $web = \Yii::getAlias('@static');
        
        $maxSize = \Yii::$app->params['imageMaxSize'];
        
        $server = $this->server ?: Url::to(['webupload']);
        $swfPath = str_replace('\\', '/', \Yii::getAlias('@common/widgets/swf'));
        $this->view->registerJs(<<<JS
        var uploader = WebUploader.create({
        auto: true,
        fileVal: 'upfile',
        // swf文件路径
        swf: '{$swfPath}/Uploader.swf',

        // 文件接收服务端。gy
        server: '{$server}',

        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick: {
            id:'#{$this->options['boxId']}',
            //innerHTML:'{$this->options['innerHTML']}'
            multiple:false,
        },
        compress:false,//配置压缩的图片的选项。如果此选项为false, 则图片在上传前不进行压缩。
        chunked:true,// [默认值：false] 是否要分片处理大文件上传。
        chunkSize:3072000,//[默认值：5242880] 如果要分片，分多大一片？ 默认大小为5M.
        fileSingleSizeLimit:{$maxSize},//验证单个文件大小是否超出限制, 超出则不允许加入队列。
        accept: {
            title: 'Images',
            extensions: 'gif,jpg,jpeg,bmp,png',
            mimeTypes: 'image/jpg,image/jpeg,image/png'
        }
        // 不压缩image, 默认如果是jpeg，文件上传前会压缩一把再上传！
        //resize: false
    });
    
   uploader.onError = function( code ) {
        if(code == 'F_EXCEED_SIZE') {
           alert( '上传文件过大，请重新选择');
           return false;
        }

    };
    uploader.on('beforeFileQueued',function(file){
        var size = $('#uploader ul li').size();
        if(size >={$this->options['number']}) {
           alert('上传图片不能超过（{$this->options['number']}）张上限');
           return false;
        }
    });
    uploader.on( 'uploadProgress', function( file, percentage ) {
        var li = $( '#'+file.id ),
        percent = li.find('.progress .progress-bar');

        // 避免重复创建
        if ( !percent.length ) {
            percent = $('<div class="progress progress-striped active">' +
              '<div class="progress-bar" role="progressbar" style="width: 0%">' +
              '</div>' +
            '</div>').appendTo( li ).find('.progress-bar');
        }

         li.find('p.state').text('上传中 '+ (percentage * 100).toFixed(1) + '%');

         percent.css( 'width', percentage * 100 + '%' );
    });
   
    // 完成上传完了，成功或者失败，先删除进度条。
    uploader.on( 'uploadSuccess', function( file, data ) {
        if(data.flag) {
            addFile(file,data);
            $( '#'+file.id ).find('p.state').text('上传成功').fadeOut();
            var tempId = $( '#{$this->options['id']}' ).val();
                tempId += tempId == '' ? '' : ',';
            var newval = tempId+data.id;
            
            $( '#{$this->options['id']}' ).val(newval);
            
        }else {
           alert(data.state);
           return false;
        }
        
    });
    
   uploader.on('error',function(type){
       if(type == 'Q_TYPE_DENIED') {
          alert('上传图片支持jpg、jpeg，gif，png，bmp格式');
          return false;
       }
    });
    
    function addFile(file,data) {
       var li = $( '<li dataid="'+data.id+'" id="' + file.id + '">' +
                '<p class="title">' + file.name + '</p>' +
                '<p class="imgWrap"><img src="{$web}'+data.url+'" width="{$this->options['previewWidth']}" height="{$this->options['previewHeight']}"/></p>'+
                '<p class="progress"><span></span></p>' +
                '</li>');
                
        var li_items = '<span data-id="'+data.id+'" class="removeItems cancel">删除</span>';
            //li_items +='<span data-id="'+data.id+'" class="rotateRight">向右旋转</span>';
            //li_items +='<span data-id="'+data.id+'" class="rotateLeft">向左旋转</span>';
            li_items +='<span data-id="'+data.id+'" class="moveRight">向右移动</span>';
            li_items +='<span data-id="'+data.id+'" class="moveLeft">向左移动</span>'; 
        var btns = $('<div class="file-panel">'+li_items+'</div>').appendTo(li);
        $('.filelist').append(li); 
    }
    
    //移动、删除操作后，重新排序隐藏域值顺序
    function updateImageId(){
        var idStr = $("#uploader li").map(function(){return $(this).attr("dataid")}).get();
        $("#{$this->options['id']}").val(idStr);
    }
            
    $(function(){
         //删除
         $('body').delegate('.removeItems','click',function(){
             $(this).parent().parent().find('li').off().end().remove();  
             updateImageId();
         });
            
        $('body').on("click", ".moveLeft", function(){
            var currentItem = $(this).closest("li");
            var prevItem = currentItem.prev("li");
            prevItem.size()>0&&currentItem.insertBefore(prevItem);
            updateImageId();
        });
        $('body').on("click", ".moveRight", function(){
            var currentItem = $(this).closest("li");
            var nextItem = currentItem.next("li");
            nextItem.size()>0&&currentItem.insertAfter(nextItem);
            updateImageId();
        });
         //移上去显示
         $('body').delegate('#uploader .filelist li','mouseenter',function(){
              $(this).find('.file-panel').stop().animate({height: 30});     
         });
                    
         //移上去去除
         $('body').delegate('#uploader .filelist li','mouseleave',function(){
              $(this).find('.file-panel').stop().animate({height: 0});     
         });
                 
          Array.prototype.indexOf = function(val) {
            for (var i = 0; i < this.length; i++) {
                if (this[i] == val) return i;
             }
             return -1;
           };
           Array.prototype.remove = function(val) {
                var index = this.indexOf(val);
                if (index > -1) {
                    this.splice(index, 1);
                }
           };
     });
JS
        );
    }
} 