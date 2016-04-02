Model Based Controller for Yii2
=======================================
A base controller, which loads an ActiveRecord before every action is ran. (Except, if there is a load exception.)

If you are managing an ActiveRecord class (for this example, a Company), this base controller will load the Company from the database, so it is already available for every action.

You can specify actions, where the ActiveRecord will not be loaded or loading it will be optional. If there is no exception for an action, and the controller couldn't load the model, the controller calls the

```php
protected function handleError($error)
{
    return false;
}
```

function, which you can override, and redirect, load a company another way or just display an error message.
$error can be BaseModelController::ERROR_NO_PARAMETER (no parameter, or null) or BaseModelController::ERROR_NOT_FOUND (not found in the ddatabase)

Returning false will give an empty page, true will let framework to continue or

```php
$this->redirect([ 'somwhere/nice' ]);
return false;
```

will redirect the invalid request.

You can also specify with or joinWith statements, which is automatically handled by the controller.
Use BaseModelController::RELATION_WITH, if you don't want to add a condition. If you want to, add the condition in the modelLoadQuery($action) (like below) and use BaseModelController::RELATION_JOIN_WITH.

Loading the model will setup the ModelUrl class, which is a helper class for creating URLs. You can use the ModelUrl to automatically include the id of the loaded model.

```php
Url::to([ 'company/someaction', 'id' => <company id> ])
```
is equal to
```php
ModelUrl::to([ 'company/someaction' ]);
```

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```bash
php composer.phar require --prefer-dist blumster/yii2-model-based-controller "*"
```

or add

```json
"blumster/yii2-model-based-controller": "*"
```

to the require section of your `composer.json` file.


Usage:
After install, you can start using it. Example code:

```php
use blumster\controller\BaseModelController;

class CompanyController extends BaseModelController
{
    /**
     * @var \app\models\Company|null
     */
    protected $model = null; // Override the BaseModelController::$model, so an IDE (for example: PhpStorm) can make use of the @var <type> to use the auto-completion feature, this line is only for the auto-completion. It is optional.

    /**
     * @var string
     */
    protected $paramName = 'cid'; // defaults to 'id' in the BaseModelController
    
    /**
     * @var string
     */
    protected $idColumnName = 'main_id'; // default to 'id' in the BaseModelController
    
    /**
     * @inheritdoc
     */
    protected static function requiredWiths()
    {
        return [
            'index' => [
                'relation1' => BaseModelController::RELATION_WITH,
                'relation2' => BaseModelController::RELATION_JOIN_WITH
            ]
        ];
    }
    
    /**
     * @inheritdoc
     */
    protected static function modelLoadExceptions()
    {
        return [
            'list' => BaseModelController::LOAD_SKIP,
            'form' => BaseModelController::LOAD_OPTIONAL
        ];
    }
    
    /**
     * @inheritdoc
     */
    protected function modelLoadQuery($action)
    {
        $query = Company::find();
        
        if ($action === 'index') {
            $query->where([ 'relation2.type' => 'test_type' ]); // You can specify extra conditions, based on the action. You must use BaseModelController::RELATION_JOIN_WITH to use the relation's table in the where condition
        }
        
        return $query;
    }
    
    /**
     * Magic index action.
     */
    public function actionIndex()
    {
        return $this->render('index', [
            'model' => $this->model,
            'relation1Entries' => $this->model->relation1 // Already loaded (by requiredWiths())
        ]);
    }
    
    /**
     * Lists the Companies. This is an exception, here we doesn't have the Company, we are listing all of the Companies, so we can select one.
     *
     * @return string
     */
    public function actionList()
    {
        $provider = new ActiveDataProvider([
            'query' => Company::find(),
            'sort' => [
                'attributes' => [ 'name', 'seat', 'enabled' ]
            ],
            'pagination' => [ 'pageSize' => 20 ]
        ]);

        return $this->render('list', [
            'dataProvider' => $provider
        ]);
    }
    
    /**
     * Handles the creation and update of a Company. In this case, the Company is optional. If we have one, it's an edit form. If we don't, it's a create form.
     *
     * @return array|string|Response
     * @throws \Exception
     */
    public function actionForm()
    {
        $model = null;

        if (!is_null($this->model)) {
            $model = $this->model;
        } else {
            $model = new Company();
        }

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            if (Yii::$app->request->isAjax) {
                Yii::$app->response->format = Response::FORMAT_JSON;
                return ActiveForm::validate($model);
            }

            if ($model->save()) {
                return $this->redirect([ 'company/list' ]);
            } else {
                Yii::error('Unable to save Company! Errors: ' . print_r($model->getErrors(), true));
            }
        }

        return $this->render('form', [
            'model' => $model
        ]);
    }
}
```