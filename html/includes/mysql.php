<?php

/* * *************************************************************************
 *  Copyright (C) 2010  William Bellavance
 *                      Flintman Computers
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * ************************************************************************* */

if (!defined("SQL_LAYER")) {
    define("SQL_LAYER", "mysqli");


    class sql_db {
        public $db_connect_id;
        public $query_result;
        public $row = null;
        public $rowset = null;
        public $num_queries = 0;
        public $persistency;
        public $user;
        public $password;
        public $server;
        public $dbname;

        // Constructor
        public function __construct($sqlserver, $sqluser, $sqlpassword, $database, $persistency = true) {
            $this->persistency = $persistency;
            $this->user = $sqluser;
            $this->password = $sqlpassword;
            $this->server = $sqlserver;
            $this->dbname = $database;

            $this->db_connect_id = new \mysqli($this->server, $this->user, $this->password, $this->dbname);
            if ($this->db_connect_id->connect_error) {
                die('Connect Error (' . $this->db_connect_id->connect_errno . ') ' . $this->db_connect_id->connect_error);
            }
        }

        public function sql_close() {
            if ($this->db_connect_id) {
                if ($this->query_result) {
                    $this->query_result->free();
                }
                $result = $this->db_connect_id->close();
                return $result;
            } else {
                return false;
            }
        }

        public function sql_query($query = "", $transaction = FALSE) {
            $this->query_result = null;
            $this->row = null;
            $this->rowset = null;
            if ($query != "") {
                $this->num_queries++;
                $this->query_result = $this->db_connect_id->query($query);
            }
            if ($this->query_result) {
                return $this->query_result;
            } else {
                return false;
            }
        }

        public function sql_numrows($query_id = null) {
            if (!$query_id) {
                $query_id = $this->query_result;
            }
            if ($query_id) {
                return $query_id->num_rows;
            } else {
                return false;
            }
        }

        public function sql_affectedrows() {
            if ($this->db_connect_id) {
                return $this->db_connect_id->affected_rows;
            } else {
                return false;
            }
        }

        public function sql_numfields($query_id = null) {
            if (!$query_id) {
                $query_id = $this->query_result;
            }
            if ($query_id) {
                return $query_id->field_count;
            } else {
                return false;
            }
        }

        public function sql_fieldname($offset, $query_id = null) {
            if (!$query_id) {
                $query_id = $this->query_result;
            }
            if ($query_id) {
                $fields = $query_id->fetch_fields();
                return $fields[$offset]->name ?? false;
            } else {
                return false;
            }
        }

        public function sql_fieldtype($offset, $query_id = null) {
            if (!$query_id) {
                $query_id = $this->query_result;
            }
            if ($query_id) {
                $fields = $query_id->fetch_fields();
                return $fields[$offset]->type ?? false;
            } else {
                return false;
            }
        }

        public function sql_fetchrow($query_id = null) {
            if (!$query_id) {
                $query_id = $this->query_result;
            }
            if ($query_id) {
                $this->row = $query_id->fetch_assoc();
                return $this->row;
            } else {
                return false;
            }
        }

        public function sql_fetchrowset($query_id = null) {
            if (!$query_id) {
                $query_id = $this->query_result;
            }
            if ($query_id) {
                $this->rowset = [];
                while ($row = $query_id->fetch_assoc()) {
                    $this->rowset[] = $row;
                }
                return $this->rowset;
            } else {
                return false;
            }
        }

        public function sql_fetchfield($field, $rownum = -1, $query_id = null) {
            if (!$query_id) {
                $query_id = $this->query_result;
            }
            if ($query_id) {
                $query_id->data_seek($rownum > -1 ? $rownum : 0);
                $row = $query_id->fetch_assoc();
                return $row[$field] ?? false;
            } else {
                return false;
            }
        }

        public function sql_rowseek($rownum, $query_id = null) {
            if (!$query_id) {
                $query_id = $this->query_result;
            }
            if ($query_id) {
                return $query_id->data_seek($rownum);
            } else {
                return false;
            }
        }

        public function sql_nextid() {
            if ($this->db_connect_id) {
                return $this->db_connect_id->insert_id;
            } else {
                return false;
            }
        }

        public function sql_freeresult($query_id = null) {
            if (!$query_id) {
                $query_id = $this->query_result;
            }
            if ($query_id) {
                $query_id->free();
                return true;
            } else {
                return false;
            }
        }

        public function sql_error($query_id = null) {
            $result = [
                "message" => $this->db_connect_id->error,
                "code" => $this->db_connect_id->errno
            ];
            return $result;
        }
    }
}
?>