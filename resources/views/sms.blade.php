<html>
    <head>
        <title>SMS WITH LARAVEL TWILIO</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEAg3QhqLMpG8r+8fhAXLRk2vvoC2f3B09zVXn8CA5QIVfZOJ3BCsw2P0p/We" crossorigin="anonymous">
        <style>
            body{
                width: 98%;
                margin: auto !important;
            }
        </style>
    </head>
    <body>
    <form action="" method="post">
        @csrf
        <div class="col-md-6">
            <label class="form-label">phone</label>
            <input type="text" class="form-control" name="phone">
        </div>
        <div class="col-md-6">
            <label class="form-label">Body</label>
            <textarea class="form-control" name="message"></textarea>
        </div>
        <input type="submit" class="btn btn-primary" value="Send text">
    </form>
    </body>
</html>
