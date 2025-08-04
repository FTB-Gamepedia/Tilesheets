<?php


use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;
use MediaWiki\Title\Title;

class TilesheetsQueryTileUsagesApi extends ApiQueryBase {
	public function __construct($query, $moduleName, private ILoadBalancer $dbLoadBalancer) {
		parent::__construct($query, $moduleName, 'ts');
	}

	public function getAllowedParams() {
		return array(
			'limit' => array(
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
			),
			'from' => array(
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_DEFAULT => 0,
			),
			'tile' => array(
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_DEFAULT => 0,
				IntegerDef::PARAM_MIN => 0
			),
			'namespace' => array(
				ParamValidator::PARAM_TYPE => 'namespace',
				ParamValidator::PARAM_ISMULTI => true
			)
		);
	}

	public function getExamples() {
		return array(
			'api.php?action=query&list=tileusages&tslimit=50&tstile=20',
			'api.php?action=query&list=tileusages&tsfrom=15&tstile=400',
		);
	}

	public function execute() {
		$limit = $this->getParameter('limit');
		$from = $this->getParameter('from');
		$tileID = $this->getParameter('tile');
		$namespaces = $this->getParameter('namespace');
		$dbr = $this->dbLoadBalancer->getConnection(DB_REPLICA);

		$conditions = array(
			'tl_to = ' . intval($tileID),
			'tl_from >= ' . intval($from)
		);

		if (!empty($namespaces)) {
			$namespaceConditions = [];
			foreach ($namespaces as $ns) {
				$namespaceConditions[] = "tl_from_namespace = " . intval($ns);
			}
			$conditions[] = $dbr->makeList($namespaceConditions, LIST_OR);
		}

		$results = $dbr->select(
			'ext_tilesheet_tilelinks',
			'*',
			$conditions,
			__METHOD__,
			array('LIMIT' => $limit + 1)
		);

		$ret = array();
		$count = 0;
		foreach ($results as $res) {
			$count++;
			if ($count > $limit) {
				$this->setContinueEnumParameter('from', $res->tl_from);
				break;
			}
			$pageName = $dbr->select(
				'page',
				'page_title',
				array(
					'page_id' => $res->tl_from,
					'page_namespace' => $res->tl_from_namespace
				),
				__METHOD__
			)->current()->page_title;
			$title = Title::newFromText($pageName, $res->tl_from_namespace);

			$ret[] = array(
				'entryid' => $res->tl_to,
				'pageid' => $res->tl_from,
				'ns' => $res->tl_from_namespace,
				'title' => $title->getPrefixedText()
			);
		}

		$this->getResult()->addValue('query', 'tileusages', $ret);
	}
}