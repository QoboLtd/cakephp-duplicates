<?php
namespace Qobo\Duplicates\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Qobo\Duplicates\Controller\DuplicatesController Test Case
 */
class DuplicatesControllerTest extends IntegrationTestCase
{
    public $fixtures = [
        'plugin.CakeDC/Users.users',
        'plugin.Qobo/Duplicates.articles',
        'plugin.Qobo/Duplicates.duplicates'
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

    public function testIndexUnauthenticated()
    {
        $this->get('/duplicates/duplicates/items/Articles/byTitle');

        $this->assertResponseCode(403);
    }

    public function testIndex()
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

    public function testView()
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

    public function testViewWithInvalidID()
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

    public function testDelete()
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $data = [
            'ids' => ['00000000-0000-0000-0000-000000000002']
        ];

        $this->_sendRequest(
            '/duplicates/duplicates/delete/Articles/00000000-0000-0000-0000-000000000002',
            'DELETE',
            json_encode($data)
        );
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertSame([], $response->data);
    }

    public function testDeleteWithInvalidID()
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $data = [
            'ids' => [
                '00000000-0000-0000-0000-000000000002',
                '00000000-0000-0000-0000-000000000001' // invalid duplicate ID
            ]
        ];

        $this->_sendRequest('/duplicates/duplicates/delete/Articles', 'DELETE', json_encode($data));
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertFalse($response->success);
        $this->assertSame('Failed to delete duplicates', $response->error);
    }

    public function testFalsePositive()
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $data = ['ids' => ['00000000-0000-0000-0000-000000000003']];

        $this->post('/duplicates/duplicates/false-positive/byTitle', json_encode($data));
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertSame([], $response->data);
    }

    public function testMerge()
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        $data = [
            'data' => ['excerpt' => 'Third'],
            'ids' => ['00000000-0000-0000-0000-000000000003']
        ];

        $this->post('/duplicates/duplicates/merge/Articles/00000000-0000-0000-0000-000000000002', json_encode($data));
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());

        $response = json_decode($this->_getBodyAsString());
        $this->assertTrue($response->success);
        $this->assertSame([], $response->data);
    }

    public function testMergeWithWrongID()
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        // get duplcicates count
        $count = $this->table->find('all')->count();
        $data = [
            'data' => ['excerpt' => 'Third'],
            'ids' => ['00000000-0000-0000-0000-000000000003']
        ];

        $this->post('/duplicates/duplicates/merge/Articles/00000000-0000-0000-0000-000000000404', json_encode($data));
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());
        // duplicate records were not affected
        $this->assertSame($count, $this->table->find('all')->count());

        $response = json_decode($this->_getBodyAsString());
        $this->assertFalse($response->success);
        $this->assertSame('Failed to merge duplicates', $response->error);
    }

    public function testMergeWithWrongDuplicateIDs()
    {
        $this->session(['Auth.User.id' => '00000000-0000-0000-0000-000000000004']);

        // get duplcicates count
        $count = $this->table->find('all')->count();
        $data = [
            'data' => ['excerpt' => 'Third'],
            'ids' => ['00000000-0000-0000-0000-000000000404']
        ];

        $this->post('/duplicates/duplicates/merge/Articles/00000000-0000-0000-0000-000000000002', json_encode($data));
        $this->assertResponseCode(200);
        $this->assertJson($this->_getBodyAsString());
        // duplicate records were not affected
        $this->assertSame($count, $this->table->find('all')->count());

        $response = json_decode($this->_getBodyAsString());
        $this->assertFalse($response->success);
        $this->assertSame('Failed to delete merged duplicates', $response->error);
    }
}
