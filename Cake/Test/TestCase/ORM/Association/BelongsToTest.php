<?php
/**
 * PHP Version 5.4
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 3.0.0
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Cake\Test\TestCase\ORM\Association;

use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

/**
 * Tests BelongsTo class
 *
 */
class BelongsToTest extends \Cake\TestSuite\TestCase {

/**
 * Set up
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->company = TableRegistry::get('Companies', [
			'schema' => [
				'id' => ['type' => 'integer'],
				'company_name' => ['type' => 'string'],
				'_constraints' => [
					'primary' => ['type' => 'primary', 'columns' => ['id']]
				]
			]
		]);
		$this->client = TableRegistry::get('Clients', [
			'schema' => [
				'id' => ['type' => 'integer'],
				'client_name' => ['type' => 'string'],
				'company_id' => ['type' => 'integer'],
				'_constraints' => [
					'primary' => ['type' => 'primary', 'columns' => ['id']]
				]
			]
		]);
	}

/**
 * Tear down
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		TableRegistry::clear();
	}

/**
 * Tests that the association reports it can be joined
 *
 * @return void
 */
	public function testCanBeJoined() {
		$assoc = new BelongsTo('Test');
		$this->assertTrue($assoc->canBeJoined());
	}

/**
 * Tests that the correct join and fields are attached to a query depending on
 * the association config
 *
 * @return void
 */
	public function testAttachTo() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'foreignKey' => 'company_id',
			'sourceTable' => $this->client,
			'targetTable' => $this->company,
			'conditions' => ['Companies.is_active' => true]
		];
		$association = new BelongsTo('Companies', $config);
		$field = new IdentifierExpression('Clients.company_id');
		$query->expects($this->once())->method('join')->with([
			'Companies' => [
				'conditions' => [
					'Companies.is_active' => true,
					['Companies.id' => $field]
				],
				'table' => 'companies',
				'type' => 'LEFT'
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Companies__id' => 'Companies.id',
			'Companies__company_name' => 'Companies.company_name'
		]);
		$association->attachTo($query);
	}

/**
 * Tests that default config defined in the association can be overridden
 *
 * @return void
 */
	public function testAttachToConfigOverride() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'foreignKey' => 'company_id',
			'sourceTable' => $this->client,
			'conditions' => ['Companies.is_active' => true]
		];
		$association = new BelongsTo('Companies', $config);
		$query->expects($this->once())->method('join')->with([
			'Companies' => [
				'conditions' => [
					'Companies.is_active' => false
				],
				'type' => 'LEFT',
				'table' => 'companies',
			]
		]);
		$query->expects($this->once())->method('select')->with([
			'Companies__company_name' => 'Companies.company_name'
		]);

		$override = [
			'conditions' => ['Companies.is_active' => false],
			'foreignKey' => false,
			'fields' => ['company_name']
		];
		$association->attachTo($query, $override);
	}

/**
 * Tests that it is possible to avoid fields inclusion for the associated table
 *
 * @return void
 */
	public function testAttachToNoFields() {
		$query = $this->getMock('\Cake\ORM\Query', ['join', 'select'], [null, null]);
		$config = [
			'sourceTable' => $this->client,
			'targetTable' => $this->company,
			'conditions' => ['Companies.is_active' => true]
		];
		$association = new BelongsTo('Companies', $config);
		$field = new IdentifierExpression('Clients.company_id');
		$query->expects($this->once())->method('join')->with([
			'Companies' => [
				'conditions' => [
					'Companies.is_active' => true,
					['Companies.id' => $field]
				],
				'type' => 'LEFT',
				'table' => 'companies',
			]
		]);
		$query->expects($this->never())->method('select');
		$association->attachTo($query, ['includeFields' => false]);
	}

/**
 * Test the cascading delete of BelongsTo.
 *
 * @return void
 */
	public function testCascadeDelete() {
		$mock = $this->getMock('Cake\ORM\Table', [], [], '', false);
		$config = [
			'sourceTable' => $this->client,
			'targetTable' => $mock,
		];
		$mock->expects($this->never())
			->method('find');
		$mock->expects($this->never())
			->method('delete');

		$association = new BelongsTo('Companies', $config);
		$entity = new Entity(['company_name' => 'CakePHP', 'id' => 1]);
		$this->assertTrue($association->cascadeDelete($entity));
	}

}