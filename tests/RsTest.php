<?php

require("bootstrap.php");

class RsApiTest extends PHPUnit_Framework_TestCase
{
	public $client;
	public $bucket = BUCKET_NAME;
	public $notExistKey = "not_exist";
	public $key1 = KEY_NAME;
	public $key2 = "file_name_2";
	public $key3 = "file_name_3";
	public $key4 = "file_name_4";
	public function setUp()
	{
		$this->client = new Qiniu_Client(null);
	}

	public function testStat()
	{
		list($ret, $err) = Qiniu_RS_Stat($this->client, $this->bucket, $this->key1);
		$this->assertArrayHasKey('hash', $ret);
		$this->assertNull($err);
		list($ret, $err) = Qiniu_RS_Stat($this->client, $this->bucket, $this->notExistKey);
		$this->assertNull($ret);
		$this->assertFalse($err === null);
	}

	public function testDeleteMoveCopy()
	{
		Qiniu_RS_Delete($this->client, $this->bucket, $this->key2);
		Qiniu_RS_Delete($this->client, $this->bucket, $this->key3);

		$err = Qiniu_RS_Copy($this->client, $this->bucket, $this->key1, $this->bucket, $this->key2);
		$this->assertNull($err);
		$err = Qiniu_RS_Move($this->client, $this->bucket, $this->key2, $this->bucket, $this->key3);
		$this->assertNull($err);
		$err = Qiniu_RS_Delete($this->client, $this->bucket, $this->key3);
		$this->assertNull($err);
		$err = Qiniu_RS_Delete($this->client, $this->bucket, $this->key2);
		$this->assertNotNull($err, "delete key2 false");
	}

	public function testBatchStat()
	{
		$entries = array(new Qiniu_RS_EntryPath($this->bucket, $this->key1), new Qiniu_RS_EntryPath($this->bucket, $this->key2));
		list($ret, $err) = Qiniu_RS_BatchStat($this->client, $entries);
		$this->assertNotNull($err);
		$this->assertEquals($ret[0]['code'], 200);
		$this->assertEquals($ret[1]['code'], 612);
	}

	public function testBatchDeleteMoveCopy()
	{
		$e1 = new Qiniu_RS_EntryPath($this->bucket, $this->key1);
		$e2 = new Qiniu_RS_EntryPath($this->bucket, $this->key2);
		$e3 = new Qiniu_RS_EntryPath($this->bucket, $this->key3);
		$e4 = new Qiniu_RS_EntryPath($this->bucket, $this->key4);
		Qiniu_RS_BatchDelete($this->client, array($e2, $e3,$e4));

		$entryPairs = array(new Qiniu_RS_EntryPathPair($e1, $e2), new Qiniu_RS_EntryPathPair($e1, $e3));
		list($ret, $err) = Qiniu_RS_BatchCopy($this->client, $entryPairs);
		$this->assertNull($err);
		$this->assertEquals($ret[0]['code'], 200);
		$this->assertEquals($ret[0]['code'], 200);

		list($ret, $err) = Qiniu_RS_BatchMove($this->client, array(new Qiniu_RS_EntryPathPair($e2, $e4)));
		$this->assertNull($err);
		$this->assertEquals($ret[0]['code'], 200);

		list($ret, $err) = Qiniu_RS_BatchDelete($this->client, array($e3, $e4));
		$this->assertNull($err);
		$this->assertEquals($ret[0]['code'], 200);
		$this->assertEquals($ret[0]['code'], 200);

		Qiniu_RS_BatchDelete($this->client, array($e2, $e3, $e4));
	}

}
