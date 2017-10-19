<?php


class TilesheetsQueryTileUsagesApi extends ApiQueryBase {
	public function __construct($query, $moduleName) {
		parent::__construct($query, $moduleName, 'ts');
	}

	public function getAllowedParams() {
		return array(
			'limit' => array(
				ApiBase::PARAM_DFLT => 10,
				ApiBase::PARAM_TYPE => 'limit',
				ApiBase::PARAM_MIN => 1,
				ApiBase::PARAM_MAX => ApiBase::LIMIT_BIG1,
				ApiBase::PARAM_MAX2 => ApiBase::LIMIT_BIG2,
			),
			'from' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 0,
			),
			'tile' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 0,
				ApiBase::PARAM_MIN => 0
			),
			'namespace' => array(
				ApiBase::PARAM_TYPE => 'namespace',
				ApiBase::PARAM_ISMULTI => true
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
		$dbr = wfGetDB(DB_SLAVE);

		$conditions = array(
			'tl_to = ' . intval($tileID),
			'tl_from >= ' . intval($from)
		);

		if (!empty($namespaces)) {
			$namespaceConditions = [];
			foreach ($namespaces as $ns) {
				$namespaceConditions[] = "tl_from_namespace = $ns";
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