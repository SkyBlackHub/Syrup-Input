# Syrup.Input
An extension for Symfony 6.x that allows to flexibly manage user input data for controllers through PHP 8 attributes.

Installation:
```
# config/services.yaml
services:
  Syrup\Input\EventSubscribers\ControllerSubscriber:
    arguments:
      $json: true # activate automatic decoding of JSON data in the request body
      $data: true # activate the processing of Data attributes
      $csrf: true # activate the processing of CSRF attributes
```

Example:
```
use Syrup\Input\Attributes\CSRF;
use Syrup\Input\Attributes\Data;
use Syrup\Input\Input;

class MyController extends AbstractController
{
    #[Route(path: '/submit', methods: ['POST'])]
    #[Data('id', sources: Input::QUERY, type: 'int', required: true)]
    #[Data('urgent', sources: [Input::QUERY, Input::REQUEST], default: false)]
    #[Data('data', sources: Input::REQUEST, key: '*')]
    #[CSRF(intention: 'my_form1', parameter: 'x-token', sources: Input::HEADERS)]
    public function submit(int $id, array $data, bool $urgent): Response
    {
        // On a valid user request,
        // the the argument $id will be read from the query, 
        // all POST data will be stored in the $data argument, 
        // and the $urgent flag will be checked in the query and POST-data, and will be false if not found
        // The CSRF token with the id "my_form1" will be checked in the HTTP-headers by the key "x-token"
    }
}	
```

Another example:
```
class MyController extends AbstractController
{
    #[Route(path: '/form', methods: ['GET', 'POST'])]
    #[Data('data', sources: Input::REQUEST, key: '*')]
    #[CSRF(intention: 'my_form2', sources: Input::REQUEST, methods: 'POST')]
    public function form(?array $data = null): Response
    {
        // On a valid POST-request,
        // all POST data will be stored in the $data argument, 
        // but on a GET-request the $data argument will be null
        // The CSRF token with the id "my_form2" will be checked only on a POST-request in the POST-data by the default key "_token"
    }
}
```