<?php
namespace Qobo\Duplicates\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Qobo\Duplicates\Controller\DuplicatesController Test Case
 */
class DuplicatesControllerTest extends IntegrationTestCase
{
    private $table;

    public $fixtures = [
        'plugin.CakeDC/Users.users',
        'plugin.Qobo/Duplicates.articles',
        'plugin.Qobo/Duplicates.articles_tags',
        'plugin.Qobo/Duplicates.authors',
        'plugin.Qobo/Duplicates.comments',
        'plugin.Qobo/Duplicates.duplicates',
        'plugin.Qobo/Duplicates.tags'
    ];

    public function setUp()
    {
        parent::setUp();

        $this->table = TableRegistry::getTableLocator()->get('Qobo/Duplicates.Duplicates');

        $this->configRequest([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest'
            ]
        ]);
    }

    public function tearDown()
    {
        unset($this->table);

        parent::tearDown();
    }

    public function testIndexUnauthenticated(): void
    {
        $this->get('/duplicates/duplicates/items/Articles/byTitle');

        $this->assertResponseCode(403);
    }

    public function testIndex(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $this->get('/duplicates/duplicates/items/Articles/byTitle');
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertInternalType('object', $response->pagination);
        $this->assertNotEmpty($response->pagination);
        $this->assertInternalType('array', $response->data);
        $this->assertNotEmpty($response->data);
    }

    public function testView(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $this->get('/duplicates/duplicates/view/00000000-0000-0000-0000-000000000002/byTitle');
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertInternalType('object', $response->data);
        $this->assertNotEmpty($response->data);
        $this->assertInternalType('object', $response->data->original);
        $this->assertInternalType('array', $response->data->duplicates);
        $this->assertInternalType('array', $response->data->fields);
        $this->assertInternalType('array', $response->data->virtualFields);
    }

    public function testViewWithInvalidID(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $this->get('/duplicates/duplicates/view/00000000-0000-0000-0000-000000000404/byTitle');
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertFalse($response->success);
        $this->assertSame(
            'Failed to fetch duplicates for record with ID "00000000-0000-0000-0000-000000000404"',
            $response->error
        );
    }

    public function testDelete(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $data = [
            'ids' => [
                '00000000-0000-0000-0000-000000000003',
                '00000000-0000-0000-0000-000000000001' // non-duplicated record
            ]
        ];

        $this->_sendRequest(
            '/duplicates/duplicates/delete/Articles/00000000-0000-0000-0000-000000000002',
            'DELETE',
            $data
        );
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertSame([], $response->data);
    }

    public function testDeleteWithInvalidID(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $data = [
            'ids' => ['00000000-0000-0000-0000-000000000003']
        ];

        $this->_sendRequest(
            '/duplicates/duplicates/delete/Articles/00000000-0000-0000-0000-000000000404',
            'DELETE',
            $data
        );
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertFalse($response->success);
        $this->assertSame('Failed to delete duplicates: Record not found in table "articles"', $response->error);
    }

    public function testFalsePositive(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $data = ['ids' => ['00000000-0000-0000-0000-000000000003']];

        $this->post('/duplicates/duplicates/false-positive/byTitle', $data);
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertSame([], $response->data);
    }

    public function testMerge(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        // get duplcicates count
        $count = $this->table->find('all')->count();

        $table = TableRegistry::get('Articles');
        $associations = $table->associations()->keys();

        $data = [
            'data' => ['excerpt' => 'Third'],
            'ids' => [
                '00000000-0000-0000-0000-000000000003',
                '00000000-0000-0000-0000-000000000001', // non-duplicated record
                '00000000-0000-0000-0000-000000000404' // non-existing record
            ]
        ];
        $invalidDuplicate = $table->get($data['ids'][1], ['contain' => $associations]);

        $this->post('/duplicates/duplicates/merge/Articles/00000000-0000-0000-0000-000000000002', $data);
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        // assert invalid duplicate was not affected
        $this->assertEquals($invalidDuplicate, $table->get($data['ids'][1], ['contain' => $associations]));
        $this->assertSame($count - 1, $this->table->find('all')->count());

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertSame([], $response->data);
    }

    public function testMergeWithInvalidID(): void
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        // get duplcicates count
        $count = $this->table->find('all')->count();
        // invalid ID
        $id = '00000000-0000-0000-0000-000000000404';
        $data = [
            'data' => ['excerpt' => 'Third'],
            'ids' => ['00000000-0000-0000-0000-000000000003']
        ];

        $this->post('/duplicates/duplicates/merge/Articles/' . $id, $data);
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());
        // duplicate records were not affected
        $this->assertSame($count, $this->table->find('all')->count());

        $response = json_decode($this->_getBodyAsString());
        $this->assertFalse($response->success);
        $this->assertSame('Failed to merge duplicates: Record not found in table "articles"', $response->error);
    }
}
