<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class SqlController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['shell', 'run'],
                'rules' => [
                    [
                        'actions' => ['shell', 'run'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays SQL shell.
     *
     * @return string
     */
    public function actionShell()
    {
        return $this->render('shell');
    }
    
    /**
     * Run SQL script and return result.
     *
     * @return string
     */
    public function actionRun()
    {
        $script = Yii::$app->request->post('script');
        $output = '';
        if (!empty($script)) {
            $params = Yii::$app->request->post('params');
            $useFile = Yii::$app->request->post('usefile') === 'true' ? true: false;
            $username = Yii::$app->getDb()->username;
            $password = Yii::$app->getDb()->password;
            $dbname = 'flexap';
            $command = "mysql -u $username -p$password";
            
            if (!$useFile) {
                $script = str_replace("'", "\\'", $script);
                $command .= " -e '$script'";
            }
            $command .= " $params $dbname";
            
            if ($useFile) {
                $scriptPath = tempnam(sys_get_temp_dir(), 'script');
                $handle = fopen($scriptPath, 'w');
                fwrite($handle, $script);
                fclose($handle);
                
                $command .= " < $scriptPath";
            }

            exec($command, $output);
            $output = implode("\n", $output) . "\n\n";
            
            if ($useFile) {
                unlink($scriptPath);
            }
        }
        return json_encode(['out' => htmlspecialchars($output)], JSON_UNESCAPED_UNICODE);
    }
}