--TEST--
MySQL PDOStatement->fetch(), PDO::FETCH_SERIALIZE
--EXTENSIONS--
pdo_mysql
--SKIPIF--
<?php
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'mysql_pdo_test.inc');
MySQLPDOTest::skip();
?>
--FILE--
<?php
    require_once(__DIR__ . DIRECTORY_SEPARATOR . 'mysql_pdo_test.inc');
    $db = MySQLPDOTest::factory();

    try {

        class myclass implements Serializable {

            public function __construct($caller = null) {
                printf("%s(%s) - note that it must not be called when unserializing\n", __METHOD__, var_export($caller, true));
            }

            public function __set($prop, $value) {
                printf("%s(%s, %s)\n", __METHOD__, var_export($prop, true), var_export($value, true));
                $this->{$prop} = $value;
            }

            public function serialize() {
                printf("%s()\n", __METHOD__);
                return 'Value from serialize()';
            }

            public function unserialize($data) {
                printf("%s(%s)\n", __METHOD__, var_export($data, true));
            }

        }

        printf("Lets see what the Serializeable interface makes our object behave like...\n");
        $obj = new myclass('Called by script');
        $tmp = unserialize(serialize($obj));
        var_dump($tmp);

        printf("\nAnd now magic PDO using fetchAll(PDO::FETCH_CLASS|PDO::FETCH_SERIALIZE)...\n");
        $db->exec(sprintf('CREATE TABLE test_stmt_fetchserialize_simple(myobj BLOB) ENGINE=%s', MySQLPDOTest::getTableEngine()));
        $db->exec("INSERT INTO test_stmt_fetchserialize_simple(myobj) VALUES ('Data fetched from DB to be given to unserialize()')");

        $stmt = $db->prepare('SELECT myobj FROM test_stmt_fetchserialize_simple');
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_SERIALIZE, 'myclass', array('Called by PDO'));
        var_dump($rows[0]);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_SERIALIZE, 'myclass');
        var_dump($rows[0]);

        printf("\nAnd now PDO using setFetchMode(PDO::FETCH:CLASS|PDO::FETCH_SERIALIZE) + fetch()...\n");
        $stmt = $db->prepare('SELECT myobj FROM test_stmt_fetchserialize_simple');
        $stmt->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_SERIALIZE, 'myclass', array('Called by PDO'));
        $stmt->execute();
        var_dump($stmt->fetch());

    } catch (PDOException $e) {
        printf("[001] %s [%s] %s\n",
            $e->getMessage(), $db->errorCode(), implode(' ', $db->errorInfo()));
    }

    print "done!\n";
?>
--CLEAN--
<?php
require __DIR__ . '/mysql_pdo_test.inc';
$db = MySQLPDOTest::factory();
$db->exec('DROP TABLE IF EXISTS test_stmt_fetchserialize_simple');
?>
--EXPECTF--
Deprecated: %s implements the Serializable interface, which is deprecated. Implement __serialize() and __unserialize() instead (or in addition, if support for old PHP versions is necessary) in %s on line %d
Lets see what the Serializeable interface makes our object behave like...
myclass::__construct('Called by script') - note that it must not be called when unserializing
myclass::serialize()
myclass::unserialize('Value from serialize()')
object(myclass)#%d (0) {
}

And now magic PDO using fetchAll(PDO::FETCH_CLASS|PDO::FETCH_SERIALIZE)...

Deprecated: PDOStatement::fetchAll(): The PDO::FETCH_SERIALIZE mode is deprecated in %s on line %d
myclass::unserialize('Data fetched from DB to be given to unserialize()')
object(myclass)#%d (0) {
}

Deprecated: PDOStatement::fetchAll(): The PDO::FETCH_SERIALIZE mode is deprecated in %s on line %d
myclass::unserialize('Data fetched from DB to be given to unserialize()')
object(myclass)#%d (0) {
}

And now PDO using setFetchMode(PDO::FETCH:CLASS|PDO::FETCH_SERIALIZE) + fetch()...

Deprecated: PDOStatement::setFetchMode(): The PDO::FETCH_SERIALIZE mode is deprecated in %s on line %d

Deprecated: PDOStatement::fetch(): The PDO::FETCH_SERIALIZE mode is deprecated in %s on line %d
myclass::unserialize('Data fetched from DB to be given to unserialize()')
object(myclass)#%d (0) {
}
done!
