# LocalDB

LocalDB is s a Basic Key Value JSON based Database

## Installation

The recommended way to install is via Composer:

```bash
git clone https://github.com/FurkanTasci/LocalDB.git
composer install
```

## Usage

```php
<?php
    require('vendor/autoload.php');

    use LocalDB/LocalDB;

    // give the Directory
    $db = new LocalDB('database');
    
    // insert and create new database
    $db->insert('users', [
        'id' => 1,
        'name' => 'Furkan',
        'lastname' => 'Tasci',
        'age' => 27
    ]);
    
    // delete colunm
    $db->delete('users')->where([
        'id' => 1
    ])->run();
    
    // delete with AND statement
    $db->delete('users')->where([
        'id' => 2,
        'name' => 'name8'
    ], 'AND')->run();
?>
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## ToDO:
    - update rows
    - order by
    - cache

## License
[MIT](https://choosealicense.com/licenses/mit/)
