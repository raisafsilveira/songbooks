<?php

function uniqidReal($length=8)
{
    if (function_exists("random_bytes")) {
        $bytes = random_bytes(ceil($length / 2));
    } elseif (function_exists("openssl_random_pseudo_bytes")) {
        $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
    } else {
        throw new Exception("no cryptographically secure random function available");
    }
    return substr(bin2hex($bytes), 0, $length);
}

function checkFile($file)
{
    
}

function queryDb($cid, $sql, $id=false)
{
    $result = array('status'=>"",'msg'=>"ok");

    if ($cid->connect_errno)
    {
        $result['status']="ko";
        $result['msg']="Error establishing a database connection ".$cid->connect_error;
        return $result;
    }

    try
    {
        $res = $cid->multi_query($sql);
    } catch (Exception $e)
    {
        $result['status']="ko";
        $result['msg']="Caught exception: ".$e->getMessage();
        return $result;
    }
    
    while(mysqli_next_result($cid)){;}

    if ($res==null)
    {
        $result['status']="ko";
        $result['msg']="Query error: ".$cid->error;
    } else
    {
        $result['status']="ok";
        $result['msg']="Query successfully executed";
        if ($id)
            $result['recordId'] = $cid->insert_id;
    }    
    return $result;
}

function queryRecordExist($cid, $sql, $record=null)
{
    $result = array('status'=>"",'msg'=>"ok");

    if ($cid->connect_errno)
    {
        $result['status']="ko";
        $result['msg']="Error establishing a database connection ".$cid->connect_error;
        return $result;
    }

    try
    {
        $res = $cid->query($sql);
    } catch (Exception $e)
    {
        $result['status']="ko";
        $result['msg']="Caught exception: ".$e->getMessage();
        return $result;
    }
    
    if ($res==null)
    {
        $result['status']="ko";
        $result['msg']='Query error: '.$cid->error;
    } else
    {
        $result['status']="ok";
        $result['msg'] = ($res->num_rows > 0) ? 1 : 0;
        if ($record)
            $result['recordId'] = $res->fetch_assoc()[$record];
    }
    return $result;    
}

function queryWithResultSet($cid, $sql, &$resultSet)
{
	$result = array("status"=>"ok","msg"=>"");

    if ($cid->connect_errno)
    {
        $result['status']="ko";
        $result['msg']="Error establishing a database connection ".$cid->connect_error;
        return $result;
    }

    try
    {
        $res = $cid->query($sql);
    } catch (Exception $e)
    {
        $result['status']="ko";
        $result['msg']="Caught exception: ".$e->getMessage();
        return $result;
    }
    
    if ($res==null)
    {
        $result['status']="ko";
        $result['msg']="Query error: ".$cid->error;
	} else
    {
        $result['status']="ok";
        $result['msg']="Query successfully executed";
		while($row=$res->fetch_assoc())
            $resultSet[] = $row;
    }    
    return $result;
}
?>