<?php
	use fruithost\ModuleInterface;
	use fruithost\Database;
	use fruithost\Auth;
	use fruithost\Button;
	use fruithost\Modal;
	use fruithost\I18N;
	
	class Cronjob extends ModuleInterface {
		private $cronjobs = [];
		
		public function init() {
			$this->addModal((new Modal('create_cron', I18N::get('Create Cronjob'), __DIR__ . '/views/form.php'))->addButton([
				(new Button())->setName('cancel')->setLabel(I18N::get('Cancel'))->addClass('btn-outline-danger')->setDismissable(),
				(new Button())->setName('create')->setLabel(I18N::get('Create'))->addClass('btn-outline-success')
			])->onSave([ $this, 'onSave' ]));
			
			$this->addModal((new Modal('change_cron', I18N::get('Change Cronjob'), __DIR__ . '/views/form.php'))->addButton([
				(new Button())->setName('cancel')->setLabel(I18N::get('Cancel'))->addClass('btn-outline-danger')->setDismissable(),
				(new Button())->setName('update')->setLabel(I18N::get('Update'))->addClass('btn-outline-success')
			])->onSave([ $this, 'onUpdate' ]));
			
			$this->cronjobs = Database::fetch('SELECT * FROM `' . DATABASE_PREFIX . 'cronjobs` WHERE `user_id`=:user ORDER BY `id` ASC', [
				'user'	=> Auth::getID()
			]);
		}
		
		public function load() {
			if(empty($this->cronjobs)) {
				$this->addButton((new Button())->setName('create')->setLabel(I18N::get('Create'))->addClass('btn-outline-success')->setModal('create_cron'));
			} else {
				$this->addButton([
					(new Button())->setName('create')->setLabel(I18N::get('Create New'))->addClass('btn-outline-success')->setModal('create_cron'),
					(new Button())->setName('delete')->setLabel(I18N::get('Delete selected'))->addClass('btn-outline-danger')
				]);				
			}
		}
		
		public function onUpdate($data = []) {
			if(!isset($data['cron_id']) || empty($data['cron_id'])) {
				return I18N::get('Unknown error occurs!');
			}
			
			if(!isset($data['minute']) || !($data['minute'] == '*' || ($data['minute'] >= 0 && $data['minute'] <= 59))) {
				return I18N::get('Please enter a valid minute!');
			}
			
			if(!isset($data['hour']) || !($data['hour'] == '*' || ($data['hour'] >= 0 && $data['hour'] <= 23))) {
				return I18N::get('Please enter a valid hour!');
			}
			
			if(!isset($data['day']) || !($data['day'] == '*' || ($data['day'] >= 1 && $data['day'] <= 31))) {
				return I18N::get('Please enter a valid day!');
			}
			
			if(!isset($data['month']) || !($data['month'] == '*' || ($data['month'] >= 1 && $data['month'] <= 12))) {
				return I18N::get('Please enter a valid month!');
			}
			
			if(!isset($data['weekday']) || !($data['weekday'] == '*' || ($data['weekday'] >= 0 && $data['weekday'] <= 6))) {
				return I18N::get('Please enter a valid weekday!');
			}
			
			switch($data['type']) {
				case 'php':
					if(!isset($data['php']) || !preg_match('/\.php$/', $data['php'])) {
						return I18N::get('Please enter a valid PHP script!');
					}
					
					Database::update(DATABASE_PREFIX . 'cronjobs', [ 'id', 'user_id' ], [
						'id'			=> $data['cron_id'],
						'user_id'		=> Auth::getID(),
						'task_minute'	=> $data['minute'],
						'task_hour'		=> $data['hour'],
						'task_day'		=> $data['day'],
						'task_month'	=> $data['month'],
						'task_weekday'	=> $data['weekday'],
						'command_type'	=> 'PHP',
						'command_value'	=> json_encode([
							'script'		=> $data['php'],
							'parameters'	=> $data['parameters']
						]),
						'name'			=> (empty($data['name']) ? NULL : $data['name'])
					]);
					
					return true;
				break;
				case 'url':
					if(!isset($data['url']) || !preg_match('/^(http|https):\/\//Uis', $data['url'])) {
						return I18N::get('Please enter a valid URL!');
					}
					
					Database::update(DATABASE_PREFIX . 'cronjobs', [ 'id', 'user_id' ], [
						'id'			=> $data['cron_id'],
						'user_id'		=> Auth::getID(),
						'task_minute'	=> $data['minute'],
						'task_hour'		=> $data['hour'],
						'task_day'		=> $data['day'],
						'task_month'	=> $data['month'],
						'task_weekday'	=> $data['weekday'],
						'command_type'	=> 'URL',
						'command_value'	=> $data['url'],
						'name'			=> (empty($data['name']) ? NULL : $data['name'])
					]);
					
					return true;
				break;
				default:
					return I18N::get('Please enter a valid action!');
				break;
			}
		}
		
		public function onPOST($data = []) {
			if(isset($data['action'])) {
				switch($data['action']) {
					case 'delete':
						if(!isset($data['cronjob'])) {
							$this->assign('error', I18N::get('Please select an Cronjob you want to delete!'));
						} else {
							$deletion	= [];
							$stop		= false;
							
							foreach($data['cronjob'] AS $cronjob) {
								if(Database::exists('SELECT * FROM `' . DATABASE_PREFIX . 'cronjobs` WHERE `id`=:cronjob AND `user_id`=:user_id LIMIT 1', [
									'cronjob'		=> $cronjob,
									'user_id'		=> Auth::getID()
								])) {
									$deletion[] = Database::single('SELECT * FROM `' . DATABASE_PREFIX . 'cronjobs` WHERE `id`=:cronjob LIMIT 1', [
										'cronjob'	=> $cronjob
									]);
								} else {
									$stop	= true;
									$unable = Database::single('SELECT * FROM `' . DATABASE_PREFIX . 'cronjobs` WHERE `id`=:cronjob LIMIT 1', [
										'cronjob'	=> $cronjob
									]);
									
									if(empty($unable)) {
										$this->assign('error', I18N::get('An unknown error has occurred. Please retry your action!'));
									} else {
										$this->assign('error', sprintf(I18N::get('You have no permissions to delete the Cronjob <strong>%s_%s</strong>!'), (empty($cronjob->name) ? sprintf('<i>%s</i>', I18N::get('Not named')) : $cronjob->name)));
									}
									break;
								}
							}
							
							if(!$stop) {
								$cronjobs = [];
								
								foreach($deletion AS $cronjob) {
									$cronjobs[] = (empty($cronjob->name) ? I18N::get('Not named') : $cronjob->name);
									
									Database::delete(DATABASE_PREFIX . 'cronjobs', [
										'id'			=> $cronjob->id
									]);
								}
								
								$this->assign('success', sprintf(I18N::get('Following Cronjobs was deleted: <strong><br />- %s</strong>'), implode('<br />- ', $cronjobs)));
							}
							
							$this->cronjobs = Database::fetch('SELECT * FROM `' . DATABASE_PREFIX . 'cronjobs` WHERE `user_id`=:user ORDER BY `id` ASC', [
								'user'	=> Auth::getID()
							]);
						}
					break;
				}
			}
		}
		
		public function onSave($data = []) {
			if(!isset($data['minute']) || !($data['minute'] == '*' || ($data['minute'] >= 0 && $data['minute'] <= 59))) {
				return I18N::get('Please enter a valid minute!');
			}
			
			if(!isset($data['hour']) || !($data['hour'] == '*' || ($data['hour'] >= 0 && $data['hour'] <= 23))) {
				return I18N::get('Please enter a valid hour!');
			}
			
			if(!isset($data['day']) || !($data['day'] == '*' || ($data['day'] >= 1 && $data['day'] <= 31))) {
				return I18N::get('Please enter a valid day!');
			}
			
			if(!isset($data['month']) || !($data['month'] == '*' || ($data['month'] >= 1 && $data['month'] <= 12))) {
				return I18N::get('Please enter a valid month!');
			}
			
			if(!isset($data['weekday']) || !($data['weekday'] == '*' || ($data['weekday'] >= 0 && $data['weekday'] <= 6))) {
				return I18N::get('Please enter a valid weekday!');
			}
			
			switch($data['type']) {
				case 'php':
					if(!isset($data['php']) || !preg_match('/\.php$/', $data['php'])) {
						return I18N::get('Please enter a valid PHP script!');
					}
					
					$id = Database::insert(DATABASE_PREFIX . 'cronjobs', [
						'id'			=> null,
						'user_id'		=> Auth::getID(),
						'task_minute'	=> $data['minute'],
						'task_hour'		=> $data['hour'],
						'task_day'		=> $data['day'],
						'task_month'	=> $data['month'],
						'task_weekday'	=> $data['weekday'],
						'command_type'	=> 'PHP',
						'command_value'	=> json_encode([
							'script'		=> $data['php'],
							'parameters'	=> $data['parameters']
						]),
						'name'			=> (empty($data['name']) ? NULL : $data['name']),
						'time_created'	=> date('Y-m-d H:i:s', time()),
						'time_running'	=> NULL
					]);
					
					return true;
				break;
				case 'url':
					if(!isset($data['url']) || !preg_match('/^(http|https):\/\//Uis', $data['url'])) {
						return I18N::get('Please enter a valid URL!');
					}
					
					$id = Database::insert(DATABASE_PREFIX . 'cronjobs', [
						'id'			=> null,
						'user_id'		=> Auth::getID(),
						'task_minute'	=> $data['minute'],
						'task_hour'		=> $data['hour'],
						'task_day'		=> $data['day'],
						'task_month'	=> $data['month'],
						'task_weekday'	=> $data['weekday'],
						'command_type'	=> 'URL',
						'command_value'	=> $data['url'],
						'name'			=> (empty($data['name']) ? NULL : $data['name']),
						'time_created'	=> date('Y-m-d H:i:s', time()),
						'time_running'	=> NULL
					]);
					
					return true;
				break;
				default:
					return I18N::get('Please enter a valid action!');
				break;
			}
		}
		
		public function content() {
			if(empty($this->cronjobs)) {
				require_once('views/empty.php');
			} else {
				require_once('views/list.php');
			}
		}
	}
?>