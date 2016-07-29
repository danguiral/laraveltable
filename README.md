## Laravel Table ##

### Installation ###

composer.json file require  :
```
    require : {
        "fdanguiral/laraveltable": "master-dev"
    }
```

Update Composer :
```
    composer update
```

update config/app.php :

Provider :

```
    'LaravelTable\Table\TableServiceProvider::class',

```
alias
```
    'Table' => laraveltable\TableServiceProvider::class,

```

### Publication ###

```
    php artisan vendor:publish --provider=LaravelTable\Table\TableServiceProvider
```

plugin is installed

### includ js le JS ###

```
    <script src="{{ asset('/vendor/laraveltable/js/laraveltable-sortable.js') }}"></script>
```

### How to use ###

controller :
```
   //init table
   $table = new Table('table-name');

   //select is not required, the default value is select *
   $table->select = ['users.*','public_users.id as public_user_id','public_users.birthday'];

   //displayed columns , the key value is the table name and value is the displayed name
   $table->setColumnDisplayed(['address' => 'adresse', 'postal_code' => 'code postal', 'type_address' => 'type']);

   //sortable columns
   $table->setColumnSorted(['address','postal_code', 'type_address']);

   // where
   $table->addWhere(['user_id', '=', $user_id]);

   // link for actions(show, edit,delete)
   // routes must finish by /show, /update, /delete
   // url type :
   // monsite.com/table/{id}/show
   // monsite.com/table/{id}/update
   // monsite.com/table/{id}/delete
   $table->setLink(url('table'));

   // generate table :
   $table->prepareView();
```

view :

```
    // print table
    {!!$table->getHtmlTable()!!}

    // print pagination
    {!!$table->getHtmlPagination()!!}

    // print search input and button
    {!!$table->getHtmlSearch("button name")!!}

    // print button to reset search
    {!!$table->getHtmlSearch("button name")!!}

```

debug :

```
    //to show the executed query : add this line in the controller
    $table->debug = true;
```

callback to custom data displayed :

```
    ex :

    /**
    * callback to convert date in french for the view
    */
    $table->addCallBackData('birthday', function ($data) {
        if (!empty($data)) {
            $carbon = new Carbon($data);
            $data = $carbon->format('d/m/Y');
        }
        return $data;
    });
```

callback to custom search data :

```
    /**
    * callback to convert date for the research
    */
    $table->addCallBackSearch('birthday', function ($data) {
        if (preg_match("^\\d{1,2}/\\d{2}/\\d{4}^", $data)) {
            $data = Carbon::createFromFormat('d/m/Y', $data)->format('Y-m-d');
        }
        return $data;
    });
```
