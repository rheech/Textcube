<?php
/// Copyright (c) 2004-2009, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)

class Pingback {
	function Pingback() {
		$this->reset();
	}

	function reset() {
		$this->error = 
		$this->id =
		$this->url = // source URI
		$this->title = // title of source page (may include site)
		$this->ip = // IP of pingback client
		$this->isFiltered =
			null;
		// Unused: writer, site, subject, excerpt
		// Target URI is processed in the pingback server handler.
	}

	function open($filter = '', $fields = '*', $sort = 'written') {
		global $database;
		if (is_numeric($filter))
			$filter = 'AND id = ' . $filter;
		else if (!empty($filter))
			$filter = 'AND ' . $filter;
		if (!empty($sort))
			$sort = 'ORDER BY ' . $sort;
		$this->close();
		$this->_result = Data_IAdapter::query("SELECT $fields FROM {$database['prefix']}RemoteResponses WHERE blogid = ".getBlogId()." AND type = 'pingback' $filter $sort");
		if ($this->_result) {
			if ($this->_count = Data_IAdapter::num_rows($this->_result))
				return $this->shift();
			else
				Data_IAdapter::free($this->_result);
		}
		unset($this->_result);
		return false;

	}

	function close() {
		if (isset($this->_result)) {
			Data_IAdapter::free($this->_result);
			unset($this->_result);
		}
		$this->_count = 0;
		$this->reset();
	}

	function shift() {
		$this->reset();
		if ($this->_result && ($row = Data_IAdapter::fetch($this->_result))) {
			foreach ($row as $name => $value) {
				if ($name == 'blogid')
					continue;
				switch ($name) {
					case 'subject':
						$name = 'title';
						break;
					case 'written':
						$name = 'received';
						break;
				}
				$this->$name = $value;
			}
			return true;
		}
		return false;
	}

	function getCount() {
		return (isset($this->_count) ? $this->_count : 0);
	}

	function add() {
		global $database;
		if (!isset($this->id)) $this->id = $this->nextId();
		else $this->id = $this->nextId($this->id);
		if (!isset($this->entry))
			return $this->_error('entry');
		if (!isset($this->url))
			return $this->_error('url');
		if (!isset($this->ip))
			$this->ip = $_SERVER['REMOTE_ADDR'];
		if (!$query = $this->_buildQuery())
			return false;
		if (!$query->hasAttribute('written'))
			$query->setAttribute('written', 'UNIX_TIMESTAMP()');
		if (!isset($this->isFiltered))
			$this->isFiltered = 0;
		
		if (!$query->insert())
			return $this->_error('insert');

		if ($this->isFiltered == 0) {
			// TODECIDE: include pingbacks in counting trackbacks?
			Data_IAdapter::query("UPDATE {$database['prefix']}Entries SET trackbacks = trackbacks + 1 WHERE blogid = ".getBlogId()." AND id = {$this->entry}");
		}
		return true;
	}

	function nextId($id = 0) {
		global $database;
		$maxId = Data_IAdapter::queryCell("SELECT max(id) FROM {$database['prefix']}RemoteResponses WHERE blogid = ".getBlogId());
		if($id == 0)
			return $maxId + 1;
		else
			 return ($maxId > $id ? $maxId : $id);
	}

	function _buildQuery() {
		global $database;
		$query = new Data_Table($database['prefix'] . 'RemoteResponses');
		$query->setQualifier('blogid', getBlogId());
		$query->setQualifier('type', 'pingback');
		if (isset($this->id)) {
			if (!Validator::number($this->id, 1))
				return $this->_error('id');
			$query->setQualifier('id', $this->id);
		}
		if (isset($this->entry)) {
			if (!Validator::number($this->entry, 1))
				return $this->_error('entry');
			$query->setQualifier('entry', $this->entry);
		}
		if (isset($this->url)) {
			$this->url = UTF8::lessenAsEncoding(trim($this->url), 255);
			if (empty($this->url))
				return $this->_error('url');
			$query->setQualifier('url', $this->url, true);
		}
		if (isset($this->ip)) {
			if (!Validator::ip($this->ip))
				return $this->_error('ip');
			$query->setAttribute('ip', $this->ip, true);
		}
		if (isset($this->received)) {
			if (!Validator::timestamp($this->received))
				return $this->_error('received');
			$query->setAttribute('written', $this->received);
		}
		if (isset($this->isFiltered)) {
			if ($this->isFiltered) {
				$query->setAttribute('isFiltered', 'UNIX_TIMESTAMP()');
			} else {
				$query->setAttribute('isFiltered', Validator::getBit($this->isFiltered));
			}
			
		}
		return $query;
	}

	function _error($error) {
		$this->ereror = $error;
		return false;
	}
}
?>