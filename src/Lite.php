<?php
namespace PhalApi\AliyunDomain;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class Lite {

	public function __construct($config = NULL) {
		if (is_null($config)) {
			$config = \PhalApi\DI()->config->get('app.AliyunDomain');
		}
		AlibabaCloud::accessKeyClient($config['accessKeyId'], $config['accessKeySecret'])
			->regionId('cn-hangzhou') 	// 设置客户端区域，使用该客户端且没有单独设置的请求都使用此设置
			->timeout(10) 							// 超时10秒，使用该客户端且没有单独设置的请求都使用此设置
			->connectTimeout(10) 				// 连接超时10秒，当单位小于1，则自动转换为毫秒，使用该客户端且没有单独设置的请求都使用此设置
			//->debug(true) 						// 开启调试，CLI下会输出详细信息，使用该客户端且没有单独设置的请求都使用此设置
			->asDefaultClient();
	}

	public function SetDomainRecords($domainName, $type, $rr, $value){
		$record_info = $this->rpcRequest('DescribeDomainRecords', [
			'DomainName' => $domainName
		]);
		if($record_info && !is_null($record_info['DomainRecords'])){
			$blnExist = false;
			$rec = null;
			foreach ($record_info['DomainRecords']['Record'] as $record){
				if($record['Type']===$type && $record['RR']===$rr){
					$blnExist = true;
					$rec = $record;
					break;
				}
			}
			if($blnExist){
				if($rec['Value'] != $value){
					$rs = $this->rpcRequest('UpdateDomainRecord', [
						'RecordId' => $rec['RecordId'],
						'RR' => $rr,
						'Type' => $type,
						'Value' => $value,
					]);
					return $rs;
				} else {
					return 'AliDns not change ' .$value;
				}
			} else {
				$rs = $this->rpcRequest('AddDomainRecord', [
					'DomainName' => $domainName,
					'RR' => $rr,
					'Type' => $type,
					'Value' => $value,
				]);
				return $rs;
			}
		}
		return $record_info;
	}

	public function SetDDNS($domainName, $rr, $ip){
		$record_info = $this->rpcRequest('DescribeDomainRecords', [
			'DomainName' => $domainName
		]);
		if($record_info && !is_null($record_info['DomainRecords'])){
			$blnExist = false;
			$rec = null;
			foreach ($record_info['DomainRecords']['Record'] as $record){
				if($record['Type']==='A' && $record['RR']===$rr){
					$blnExist = true;
					$rec = $record;
					break;
				}
			}
			if($blnExist){
				if($rec['Value'] != $ip){
					$rs = $this->rpcRequest('UpdateDomainRecord', [
						'RecordId' => $rec['RecordId'],
						'RR' => $rr,
						'Type' => 'A',
						'Value' => $ip,
					]);
					return $rs;
				} else {
					return 'AliDns IP not change ' .$ip;
				}
			} else {
				$rs = $this->rpcRequest('AddDomainRecord', [
					'DomainName' => $domainName,
					'Type' => 'A',
					'RR' => $rr,
					'Value' => $ip,
				]);
				return $rs;
			}
		}
		return $record_info;
	}

	private function rpcRequest($action, $params) {
		try {
			$result = AlibabaCloud::rpcRequest()
				->product('Alidns')
				->version('2015-01-09')
				->action($action)
				->method('POST')
				->options([
					'query' => $params
				])
				->request();
			if($result->isSuccess()) {
				return $result->toArray();
			} else {
				return $result;
			}
		} catch (ClientException $e) {
			\PhalApi\DI()->logger->error($e->getErrorMessage());
			return null;
		} catch (ServerException $e) {
			\PhalApi\DI()->logger->error($e->getErrorMessage());
			return null;
		}
	}

}