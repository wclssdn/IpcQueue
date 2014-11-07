<?php

/**
 * Linux IPC queue
 * @author wclssdn <wclssdn@gmail.com>
 */
class IpcQueue{

	/**
	 * 生成队列key的最小随机值
	 * @var number
	 */
	const GENERATE_KEY_MIN = 10000;

	/**
	 * 生成队列key的最大随机值
	 * @var number
	 */
	const GENERATE_KEY_MAX = 99999;

	/**
	 * 发送是否阻塞
	 * @var number
	 */
	const ATTR_SEND_BLOCKING = 1;

	/**
	 * 接收是否阻塞（alias：ATTR_RECEIVE_FLAG => MSG_IPC_NOWAIT）
	 * @var number
	 */
	const ATTR_RECEIVE_BLOCKING = 2;

	/**
	 * 发送是否序列化
	 * @var number
	 */
	const ATTR_SEND_SERIALIZE = 3;

	/**
	 * 接收是否序列化
	 * @var number
	 */
	const ATTR_RECEIVE_UNSERIALIZE = 4;

	/**
	 * 接收模式 MSG_IPC_NOWAIT | MSG_EXCEPT | MSG_NOERROR
	 * @var number
	 */
	const ATTR_RECEIVE_FLAG = 5;

	/**
	 * 队列最大长度（元素数）
	 * @var number
	 */
	const ATTR_QUEUE_MAX_NUMBER = 100;

	/**
	 * 队列的key
	 * @var number
	 */
	protected $key;

	/**
	 * 队列
	 * @var resource
	 */
	protected $queue;

	/**
	 * 最后一次出错的错误代码
	 * @var number
	 */
	protected $lastErrorCode;

	/**
	 * 默认属性
	 * @var array
	 */
	protected $attribute = array(
			self::ATTR_SEND_BLOCKING => true,
			self::ATTR_RECEIVE_BLOCKING => true,
			self::ATTR_SEND_SERIALIZE => true,
			self::ATTR_RECEIVE_UNSERIALIZE => true,
			self::ATTR_RECEIVE_FLAG => 0
	);

	public function __construct($key = null, $perms = 0666){
		$this->key = $key ? $key : $this->generateKey();
		$this->queue = $this->getQueue($this->key, $perms);
		$this->setAttribute(self::ATTR_RECEIVE_FLAG, MSG_IPC_NOWAIT | MSG_NOERROR);
	}

	/**
	 * 设置默认属性
	 * @param number $attribute __CLASS__::ATTR_*
	 * @param mixed $value
	 */
	public function setAttribute($attribute, $value){
		if ($attribute == self::ATTR_RECEIVE_BLOCKING){
			$value ? $this->attribute[self::ATTR_RECEIVE_FLAG] &= ~MSG_IPC_NOWAIT : $this->attribute[self::ATTR_RECEIVE_FLAG] |= MSG_IPC_NOWAIT;
		}
		$this->attribute[$attribute] = $value;
	}

	/**
	 * 发送消息
	 * 非阻塞模式下，如果超过队列长度(ATTR_QUEUE_MAX_NUMBER)，立刻返回false
	 * @param mixed $message
	 * @param number $type
	 * @return boolean
	 */
	public function send($message, $type = 1){
		if (!$this->attribute[self::ATTR_SEND_BLOCKING] && $this->getStatus('msg_qnum') > $this->attribute[self::ATTR_QUEUE_MAX_NUMBER]){
			return false;
		}
		return msg_send($this->queue, 1, $message, true, $this->attribute[self::ATTR_SEND_BLOCKING], $this->lastErrorCode);
	}

	/**
	 * 接收消息
	 * @param number $type
	 * @param number $maxSize 单位byte
	 * @return boolean | array (type, message) 成功返回数组，失败返回false
	 */
	public function receive($type = 0, $maxSize = 65536){
		$messageType = null;
		$message = null;
		$result = msg_receive($this->queue, $type, $messageType, $maxSize, $message, $this->attribute[self::ATTR_RECEIVE_UNSERIALIZE], $this->attribute[self::ATTR_RECEIVE_FLAG], $this->lastErrorCode);
		return $result ? array(
				'type' => $messageType,
				'message' => $message
		) : false;
	}

	/**
	 * 获取队列状态信息
	 * @param string $key
	 * @return string | array
	 */
	public function getStatus($key = null){
		$status = msg_stat_queue($this->queue);
		return $key ? $status[$key] : $status;
	}

	/**
	 * 获取最后出错代码*并重置*
	 * @return number
	 */
	public function getLastErrorCode(){
		$lastErrorCode = $this->lastErrorCode;
		$this->lastErrorCode = null;
		return $lastErrorCode;
	}

	/**
	 * 判断队列是否存在
	 * @param number $key 队列的key
	 * @return boolean
	 */
	public function exists($key){
		return msg_queue_exists($key);
	}

	/**
	 * 销毁队列
	 * @param number $key 要销毁的队列的key
	 * @return boolean
	 */
	public function destroy($key = null){
		$queue = $this->queue;
		if ($key && $this->exists($key)){
			$queue = $this->getQueue($key);
		}
		return msg_remove_queue($queue);
	}

	/**
	 * 获取一个队列
	 * @param number $key
	 * @param number $perms
	 * @return resource | boolean
	 */
	protected function getQueue($key, $perms = 0666){
		return msg_get_queue($key, $perms);
	}

	/**
	 * 生成一个key
	 * @return number
	 */
	protected function generateKey(){
		return mt_rand(self::GENERATE_KEY_MIN, self::GENERATE_KEY_MAX);
	}
}
