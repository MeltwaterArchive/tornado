<?php
namespace Test\Tornado\DataMapper\Fixtures;

use Tornado\DataMapper\DataObjectInterface;

class TestObject implements DataObjectInterface
{

    protected $id;

    protected $name;

    protected $email;

    protected $password;

    public function getPrimaryKey()
    {
        return $this->id;
    }

    public function setPrimaryKey($key)
    {
        $this->id = $key;
    }

    public function getPrimaryKeyName()
    {
        return 'id';
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password
        ];
    }

    public function loadFromArray(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->email = $data['email'];
        $this->password = $data['password'];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
