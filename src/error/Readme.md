# 错误系统(j.error.*)


*   错误(系统/用户), 等级为 E_NOTICE, E_WARNING等, 触发error_handler, 正常执行流程
*   异常(Exception), 中断当前流程, 找最近catch处理, 否则触发exception_handler, 结束页面请求
*   致命错误(E_ERROR级别), 结束页面请求, 触发shutdown_function

**使用流程**：
1.  初始化 di(error.Handle)
1.  使用error.Handle注册系统与用户错误处理
2.  用户手动抛出错误 Error::error($msg, $code), Error::warning()或者throw, 
主要区别是后者如果被catch, 不再进入错误处理流程


## 错误处理

对象：j.error.Handle

*   注册系统错误处理handle
*   注册错误管理类不同级别handle

主要职责是，当有系统或是用户错误发生，
如何处理这些错误，默认异常处理：

1.  记录错误，
2.  渲染错误


## 错误/异常

对象：j.error.Exception

描述及定义错误
*   错误等级
*   错误用户代码， 标识用户定义的错误类型，  如 error.CODE_NOT_FOUND, error.CODE_E
*   错误标题信息
*   错误调用栈
*   错误上下文信息

## 用户错误管理

类：j.error.error

主要完成以下职责

*   抛出用户错误(Error::error(), Error::warning())
*   调用注册的用户错误处理函数处理错误


# Laravel的错误处理

## 异常处理
```
public function handleException($e)
{
    if (! $e instanceof Exception) {
        $e = new FatalThrowableError($e);
    }

    $this->getExceptionHandler()->report($e);

    if ($this->app->runningInConsole()) {
        $this->renderForConsole($e);
    } else {
        $this->renderHttpResponse($e);
    }
}
```

## 致命错误处理

```
public function handleShutdown()
{
    if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
        $this->handleException($this->fatalExceptionFromError($error, 0));
    }
}
    
protected function isFatal($type)
{
    return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
}
```

## 普通错误处理

```
public function handleError($level, $message, $file = '', $line = 0, $context = [])
{
     if (error_reporting() & $level) {
         throw new ErrorException($message, 0, $level, $file, $line);
     }
 }
```