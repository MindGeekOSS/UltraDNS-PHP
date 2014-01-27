#UltraDNS-PHP
============

A PHP command line based wrapper for the UltraDNS API

## Requirments:

- PHP 5.3+

## Description:

Although the UltraDNS API is a SOAP based service, the PHP Soap module is not required as the raw XML is created for each request.

## Usage:

- After cloning the project, run "chmod u+x udns.php"
- Add your credentials to the config.php file (username/password)
- Run ./udns.php to see available methods

## License:

Copyright 2014 MindGeek, Inc.

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0 Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.