<?php
namespace PhalApi\AliyunDomain;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class Lite {

	protected $config;

	public function __construct($config = NULL) {
		$this->config = $config;
		if ($this->config === NULL) {
			$this->config = DI()->config->get('app.AliyunDomain');
		}
		AlibabaCloud::accessKeyClient($this->config['accessKeyId'], $this->config['accessKeySecret'])
			->regionId('cn-hangzhou')
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
					'RR' => $rr,
					'Type' => 'A',
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
			return $result;
		} catch (ClientException $e) {
			\PhalApi\DI()->logger->error($e->getErrorMessage());
			return null;
		} catch (ServerException $e) {
			\PhalApi\DI()->logger->error($e->getErrorMessage());
			return null;
		}
	}

}