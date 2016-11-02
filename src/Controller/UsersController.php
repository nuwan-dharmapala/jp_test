<?php
namespace App\Controller;

use Authy\AuthyApi as AuthyApi;
use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    public function initialize()
    {
        parent::initialize();
        $this->Auth->allow(['logout', 'add', 'authyCallback']);
    }

    /**
     * Index method
     *
     * @return \Cake\Network\Response|null
     */
    public function index()
    {
        $users = $this->paginate($this->Users);

        $this->set(compact('users'));
        $this->set('_serialize', ['users']);
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Network\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => []
        ]);

        $this->set('user', $user);
        $this->set('_serialize', ['user']);
    }

    /**
     * Add method
     *
     * @return \Cake\Network\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {       
        $user = $this->Users->newEntity();
        
        if ($this->request->is('post')) {
            $email = $this->request->data['email'];
            $phone = $this->request->data['phone'];
            $authy_api_key = Configure::read('authy_api_key');

            $authy_api = new AuthyApi($authy_api_key);
            $authy_user = $authy_api->registerUser($email, $phone, '94');

            if($authy_user->ok()) {
                $user = $this->Users->patchEntity($user, $this->request->data);
                $user['authy_id'] = $authy_user->id();

                if ($this->Users->save($user)) {
                    $this->Flash->success(__('The user has been saved.'));

                    return $this->redirect(['action' => 'index']);
                } else {
                    $this->Flash->error(__('The user could not be saved. Please, try again.'));
                }
            } else {
                $this->Flash->error(__('The Authy user could not be saved. Please, try again.'));
            }
        }
        
        $this->set(compact('user'));
        $this->set('_serialize', ['user']);
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Network\Response|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Users->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->data);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The user could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('user'));
        $this->set('_serialize', ['user']);
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Network\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
    
    /**
     * Signin method
     * 
     * @return \Cake\Network\Response
     */
    public function login()
    {
        if ($this->request->is('post')) {
            $user = $this->Auth->identify();
            if ($user) {
                $userTable = TableRegistry::get('Users');
                $user = $userTable->get($user['id']);
                $user->authy_status = 'unverified';
                $userTable->save($user);
                
                $params = array(
                    'api_key'=> Configure::read('authy_api_key'),
                    'message'=> 'Request to Login to jp_test demo app',
                    'details[Email]'=> $user['email'],
                );
                
                $defaults = array(
                    CURLOPT_URL => 'https://api.authy.com/onetouch/json/users/' . $user->authy_id . '/approval_requests',
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $params,
                );
                
                $ch = curl_init();
                curl_setopt_array($ch, $defaults);
                curl_exec($ch);
                curl_close($ch);
                
                $this->Auth->setUser($user);
                return $this->redirect($this->Auth->redirectUrl());
            }
            $this->Flash->error('Your username or password is incorrect.');
        }
    }
    
    /**
     * Logout Method
     * 
     * @return \Cake\Network\Response
     */
    public function logout()
    {
        $this->Flash->success('You are now logged out.');
        return $this->redirect($this->Auth->logout());
    }
    
    public function authyCallback() {
        if ($this->request->is('post')) {
            $authy_id = $this->request->data['authy_id'];
            $query =  $this->Users->findByAuthyId($authy_id);
            $results = $query->all();
            
            $user = NULL;
            if (!$results->isEmpty()) {
                $records = $results->toArray();
                $user = array_shift($records);
            }
            
            if(isset($user)) {
                $userTable = TableRegistry::get('Users');
                $user = $userTable->get($user->id);
                $user->authy_status = $this->request->data['status'];
                $userTable->save($user);
                
                return "ok";
            } else {
                return "invalid";
            }
        }
        
        exit;
    }
}
