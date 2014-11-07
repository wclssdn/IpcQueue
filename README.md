IpcQueue
========

Linux IPC Queue in PHP

### usage

<pre>
include 'IpcQueue.class.php';
$queue = new IpcQueue();
$queue->send('Hello IpcQueue!');
var_dump($queue->revice()); //array('type' => 1, 'message' => 'Hello IpcQueue!')
</pre>
