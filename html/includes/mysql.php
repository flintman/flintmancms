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
                // Log error securely instead of exposing details
                error_log('Database Connection Error: ' . $this->db_connect_id->connect_error);
                die('Database connection failed. Please contact the administrator.');
            }

            // Set charset to UTF-8 to prevent encoding-based SQL injection
            $this->db_connect_id->set_charset('utf8mb4');
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

                // Log failed queries for security monitoring
                if (!$this->query_result && error_reporting() !== 0) {
                    error_log('SQL Query Error: ' . $this->db_connect_id->error . ' | Query: ' . substr($query, 0, 200));
                }
            }
            if ($this->query_result) {
                return $this->query_result;
            } else {
                return false;
            }
        }

        /**
         * Prepared statement wrapper for new code (optional, more secure)
         *
         * Example usage:
         *   $result = $db->sql_prepare("SELECT * FROM users WHERE id = ? AND email = ?", [$id, $email]);
         *   $data = $result->fetch_assoc();
         *
         * @param string $query SQL query with ? placeholders
         * @param array $params Array of parameters to bind
         * @return mysqli_result|false Query result or false on error
         */
        public function sql_prepare($query, $params = []) {
            if (empty($query)) {
                return false;
            }

            $stmt = $this->db_connect_id->prepare($query);
            if (!$stmt) {
                error_log('SQL Prepare Error: ' . $this->db_connect_id->error . ' | Query: ' . substr($query, 0, 200));
                return false;
            }

            if (!empty($params)) {
                // Build type string (s=string, i=integer, d=double, b=blob)
                $types = '';
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                }

                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $this->num_queries++;

            // Return result for SELECT queries
            $result = $stmt->get_result();
            $stmt->close();

            return $result;
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

            // Log errors securely (don't expose to users)
            if ($result['code'] !== 0) {
                error_log('MySQL Error ' . $result['code'] . ': ' . $result['message']);
            }

            return $result;
        }

        /**
         * Escape string for SQL queries (used by quote_smart())
         *
         * @param string $value Value to escape
         * @return string Escaped value
         */
        public function sql_escape_string($value) {
            return $this->db_connect_id->real_escape_string($value);
        }
    }
}
?>