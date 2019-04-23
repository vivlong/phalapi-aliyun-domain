# 阿里云Domain扩展
PhalApi 2.x扩展类库，基于Aliyun的Domain扩展。

## 安装和配置
修改项目下的composer.json文件，并添加：  
```
    "vivlong/phalapi-aliyun-domain":"dev-master"
```
然后执行```composer update```。  

安装成功后，添加以下配置到/path/to/phalapi/config/app.php文件：  
```php
    /**
     * 阿里云Domain相关配置
     */
    'AliyunDomain' =>  array(
        'accessKeyId'       => '<yourAccessKeyId>',
        'accessKeySecret'   => '<yourAccessKeySecret>',
    ),
```
并根据自己的情况修改填充。 

## 注册
在/path/to/phalapi/config/di.php文件中，注册：  
```php
$di->aliyunDomain = function() {
        return new \PhalApi\AliyunDomain\Lite();
};
```

## 使用
使用方式：
```php
  \PhalApi\DI()->aliyunDomain->SetDomainRecords('www.abc.com', 'A', 'www', '127.0.0.1');
  \PhalApi\DI()->aliyunDomain->SetDDNS('www.abc.com', 'www', '127.0.0.1');
```  

