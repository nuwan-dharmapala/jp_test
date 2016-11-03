<?php
namespace App\Model\Entity;

use Authy\AuthyApi as AuthyApi;
use Cake\Core\Configure;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 */
class User extends Entity
{

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */
    protected $_hidden = [
        'password'
    ];
    
    protected function _setPassword($value)
    {
        $hasher = new DefaultPasswordHasher();
        return $hasher->hash($value);
    }
    
    public function verifyToken($token) {
        $authy_api_key = Configure::read('authy_api_key');
        $authy_api = new AuthyApi($authy_api_key);
        
        try {
            $verification = $authy_api->verifyToken($this->authy_id, $token);
        }
        catch (\Authy\AuthyFormatException $ex) {
            return false;
        }
        catch (\Exeption $ex) {
            return false;
        }
        

        if(isset($verification) && $verification->ok()) {
            return true;
        } else {
            return false;
        }
    }
}
