<?php

namespace j\net\base;

/**
 * Class TaskTrait
 * @package j\net\base
 */
trait TaskTrait {

    /**
     * @var TaskManager
     */
    public $taskManager;

    /**
     * @param $message
     * @param string $type
     * @param array $context
     * @return mixed
     */
    abstract protected function log($message, $type = 'info', $context = []);

    /**
     * @return taskManager
     */
    abstract protected function getTaskManager();


    /**
     * @param $id
     * @param $callback
     * @return taskManager
     */
    function regTask($id, $callback){;
        return $this->getTaskManager()->regTask($id, $callback);
    }

    /**
     * @param $tasks
     * @return $this;
     */
    function regTasks($tasks){
        foreach($tasks as $id => $callback){
            $this->getTaskManager()->regTask($id, $callback);
        }
        return $this;
    }

    /**
     * call job to task
     * @param $id
     * @return int
     */
    function task($id){
        if(!isset($this->taskManager) || !$this->taskManager->has($id)){
            $this->log("Task manager not set", "error");
            return 0;
        }

        return call_user_func_array(
            array($this->taskManager, "doTask"),
            func_get_args()
        );
    }

    function tasks() {
        if(!isset($this->taskManager)){
            $this->log("Task manager not set", "error");
        } else {
            call_user_func_array(
                array($this->taskManager, "doTasks"),
                func_get_args()
            );
        }
    }
}