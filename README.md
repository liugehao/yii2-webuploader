## 1.安装  
```
composer require ran2016/yii2-webuploader
```
## 2.使用  
>直接使用
  
```
<?= \yidashi\webuploader\Webuploader::widget(['name' => 'xxx', 'options'=>['boxId' => 'picker', 'previewWidth'=>200, 'previewHeight'=>150]])?>
```
>或者在activeForm里使用
  
```
<?= $form->field($model,'attributeName')->widget('yidashi\webuploader\Webuploader',['options'=>['boxId' => 'picker', 'previewWidth'=>200, 'previewHeight'=>150]]); ?>
```
options非必填

使用默认action处理的话controller需要添加
```
public function actions()
{
    return [
        'webupload' => 'yidashi\webuploader\WebuploaderAction'
    ];
}
```  
如果需要使用自己的上传程序处理需添加server属性
```
<?= $form->field($model,'attributeName')->widget('yidashi\webuploader\Webuploader',['server'=>'你自己的处理路由']); ?>
```