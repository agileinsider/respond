<?php

// User model
class User{

	// adds a user
	public static function Add($email, $password, $firstName, $lastName, $role, $language, $isActive, $siteId){
		
        try{
            
    		$db = DB::get();
    
        	$userUniqId = uniqid();
    		
    		$token = null;
    	
    		$timestamp = gmdate("Y-m-d H:i:s", time());
    		
    		// create a more secure password (http://www.openwall.com/articles/PHP-Users-Passwords)
    		$hash_cost_log2 = 8; // Base-2 logarithm of the iteration count used for password stretching
    		$hash_portable = FALSE; // Not portable
    		
    		$hasher = new PasswordHash($hash_cost_log2, $hash_portable);
    		$s_password = $hasher->HashPassword($password);
    		unset($hasher);
        
            $q = "INSERT INTO Users (UserUniqId, Email, Password, FirstName, LastName, Role, Language, IsActive, SiteId, Created) 
        		 	VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
     
            $s = $db->prepare($q);
            $s->bindParam(1, $userUniqId);
            $s->bindParam(2, $email);
            $s->bindParam(3, $s_password);
            $s->bindParam(4, $firstName);
            $s->bindParam(5, $lastName);
            $s->bindParam(6, $role);
            $s->bindParam(7, $language);
            $s->bindParam(8, $isActive);
            $s->bindParam(9, $siteId);
            $s->bindParam(10, $timestamp);
            
            $s->execute();
            
            return array(
                'UserId' => $db->lastInsertId(),
                'UserUniqId' => $userUniqId,
                'Email' => $email,
                'FirstName' => $firstName,
                'LastName' => $lastName,
                'Role' => $role,
                'Language' => $language,
                'IsActive' => $isActive,
                'Token' => $token
                );
                
        } catch(PDOException $e){
            die('[User::Add] PDO Error: '.$e->getMessage());
        }
	}
	
	// determines whether a login is unique
	public static function IsLoginUnique($email){

		try{

        	$db = DB::get();
    
    		$count = 0;
    	
    		$q ="SELECT Count(*) as Count FROM Users where Email = ?";
    
        	$s = $db->prepare($q);
            $s->bindParam(1, $email);
            
    		$s->execute();
    
    		$count = $s->fetchColumn();
    
    		if($count==0){
    			return true;
    		}
    		else{
    			return false;
    		}
            
        } catch(PDOException $e){
            die('[User::IsLoginUnique] PDO Error: '.$e->getMessage());
        } 
        
	}
	
	// edit user
	public static function Edit($userUniqId, $email, $password, $firstName, $lastName, $role, $language, $isActive){
		
    	try{

            $db = DB::get();
    		
            // edit basic information
    		$q = "UPDATE Users SET 
                Email = ?,
                FirstName = ?,
    			LastName = ?,
    			Role = ?,
    			Language = ?,
    			IsActive = ?
    			WHERE UserUniqId = ?";
    		
    		$s = $db->prepare($q);
            $s->bindParam(1, $email);
            $s->bindParam(2, $firstName);
            $s->bindParam(3, $lastName);
            $s->bindParam(4, $role);
            $s->bindParam(5, $language);
            $s->bindParam(6, $isActive);
            $s->bindParam(7, $userUniqId);
            
            $s->execute();
            
            // edit password
    		User::EditPassword($userUniqId, $password);
            
    	} catch(PDOException $e){
            die('[User::Edit] PDO Error: '.$e->getMessage());
        } 
	}
	
	// edits the photo
    public static function EditPhoto($userUniqId, $photoUrl){

        try{
            
            $db = DB::get();
            
            $q = "UPDATE Users SET 
                    PhotoUrl= ?
                    WHERE UserUniqId = ?";
     
            $s = $db->prepare($q);
            $s->bindParam(1, $photoUrl);
            $s->bindParam(2, $userUniqId);
            
            $s->execute();
            
		} catch(PDOException $e){
            die('[User::EditPhoto] PDO Error: '.$e->getMessage());
        }
        
	}
	
	// edits a user profile
	public static function EditProfile($userUniqId, $email, $password, $firstName, $lastName, $language){
		
    	try{

            $db = DB::get();
    		
            // edit basic information
    		$q = "UPDATE Users SET 
                Email = ?,
                FirstName = ?,
    			LastName = ?,
    			Language = ?
    			WHERE UserUniqId = ?";
    		
    		$s = $db->prepare($q);
            $s->bindParam(1, $email);
            $s->bindParam(2, $firstName);
            $s->bindParam(3, $lastName);
            $s->bindParam(4, $language);
            $s->bindParam(5, $userUniqId);
            
            $s->execute();
            
            // edit password
    		User::EditPassword($userUniqId, $password);
            
    	} catch(PDOException $e){
            die('[User::Edit] PDO Error: '.$e->getMessage());
        } 
	}
	
	// edit password
	public static function EditPassword($userUniqId, $password){
		
		try{

            $db = DB::get();

    		if($password != "temppassword"){
    			
                // create a more secure password (http://www.openwall.com/articles/PHP-Users-Passwords)
    			$hash_cost_log2 = 8; // Base-2 logarithm of the iteration count used for password stretching
    			$hash_portable = FALSE; // Not portable
    			
    			$hasher = new PasswordHash($hash_cost_log2, $hash_portable);
    			$s_password = $hasher->HashPassword($password);
    			unset($hasher);
                
    			$q = "UPDATE Users SET Token = '', 
                        Password = ?
                        WHERE UserUniqId = ?";
                
                $s = $db->prepare($q);
                $s->bindParam(1, $s_password);
                $s->bindParam(2, $userUniqId);
                
                $s->execute();
        	
    		}
            
		} catch(PDOException $e){
            die('[User::EditPassword] PDO Error: '.$e->getMessage());
        } 
	}
	
	// generate token
	public static function SetToken($userUniqId){
		
		try{

            $db = DB::get();
		
    		// create a more secure password (http://www.openwall.com/articles/PHP-Users-Passwords)
    		$hash_cost_log2 = 8; // Base-2 logarithm of the iteration count used for password stretching
    		$hash_portable = FALSE; // Not portable
    		
    		$hasher = new PasswordHash($hash_cost_log2, $hash_portable);
    		$s_token = $hasher->HashPassword($userUniqId);
    		unset($hasher);
    		
    		$q = "UPDATE Users SET Token = ? WHERE UserUniqId=?";
    		
    		$s = $db->prepare($q);
            $s->bindParam(1, $s_token);
            $s->bindParam(2, $userUniqId);
            
            $s->execute();
    		
    		return $s_token;
        
		} catch(PDOException $e){
            die('[User::SetToken] PDO Error: '.$e->getMessage());
        } 
	}
	
	// removes a user
	public static function Remove($userUniqId){
		
		try{

            $db = DB::get();
		
            $q = "DELETE FROM Users WHERE UserUniqId=?";
		
		    $s = $db->prepare($q);
            $s->bindParam(1, $userUniqId);
            
            $s->execute();
	
		} catch(PDOException $e){
            die('[User::Remove] PDO Error: '.$e->getMessage());
        } 
	}
	
	// Gets users in an site
	public static function GetUsersForSite($siteId){
		
		try{

            $db = DB::get();
            
            $q = "SELECT Users.UserId, Users.UserUniqId, Users.Email, Users.FirstName, Users.LastName, Users.PhotoUrl, 
        		    Users.Role, Users.Language, Users.IsActive, Users.SiteId, Users.Created
    			    FROM Users
    			    WHERE Users.SiteId=? ORDER BY Users.LastName";
                    
            $s = $db->prepare($q);
            $s->bindParam(1, $siteId);
            
            $s->execute();
            
            $arr = array();
            
        	while($row = $s->fetch(PDO::FETCH_ASSOC)) {  
                array_push($arr, $row);
            } 
            
            return $arr;
        
		} catch(PDOException $e){
            die('[User::GetUsersForSite] PDO Error: '.$e->getMessage());
        } 
		
	}

	// Gets a user for a specific email and password
	public static function GetByEmailPassword($email, $password){
		
        try{
         
            $db = DB::get();
            
            $q = "SELECT UserId, UserUniqId, Email, Password, FirstName, LastName, PhotoUrl,
            		Role, Language, IsActive, SiteId, Created, Token 
        			FROM Users WHERE Email=? AND IsActive = 1";
            
            $s = $db->prepare($q);
            $s->bindParam(1, $email);
            
            $s->execute();
            
            $row = $s->fetch(PDO::FETCH_ASSOC);
        
            if($row){
                
                $hash = $row["Password"];
        	
    			// need to check the password
    			$hash_cost_log2 = 8; // Base-2 logarithm of the iteration count used for password stretching
    			$hash_portable = FALSE; // Not portable
    		
    			$hasher = new PasswordHash($hash_cost_log2, $hash_portable);
    			
    			if($hasher->CheckPassword($password, $hash)){ // success
    				unset($hasher);
    				return $row;
    			}
    			else{ // failure
    				unset($hasher);
    				return null;
    			}
    			
    		}
            
	    } catch(PDOException $e){
            die('[User::GetByEmailPassword] PDO Error: '.$e->getMessage());
        }
        
	}
	
	// Gets a user for a specific email
	public static function GetByEmail($email){
        
        try{
    	
    		$db = DB::get();
            
            $q = "SELECT UserId, UserUniqId, Email, Password, FirstName, LastName, PhotoUrl,
            		Role, Language, IsActive, SiteId, Created, Token 
        			FROM Users WHERE Email=?";
                    
            $s = $db->prepare($q);
            $s->bindParam(1, $email);
            
            $s->execute();
            
            $row = $s->fetch(PDO::FETCH_ASSOC);        
    
    		if($row){
    			return $row;
    		}
        
        } catch(PDOException $e){
            die('[User::GetByEmail] PDO Error: '.$e->getMessage());
        }
        
	}
	
	// Gets a user for a specific token
	public static function GetByToken($token){

        try{
        
    		$db = DB::get();
            
            $q = "SELECT UserId, UserUniqId, Email, Password, FirstName, LastName, PhotoUrl,
            		Role, Language, IsActive, SiteId, Created 
        			FROM Users WHERE Token=?";
                    
            $s = $db->prepare($q);
            $s->bindParam(1, $token);
            
            $s->execute();
            
            $row = $s->fetch(PDO::FETCH_ASSOC);        
    
    		if($row){
    			return $row;
    		}
        
        } catch(PDOException $e){
            die('[User::GetByToken] PDO Error: '.$e->getMessage());
        }
        
	}
	
	// Gets a user for a specific userid
	public static function GetByUserUniqId($userUniqId){
        
        try{
        
        	$db = DB::get();
            
            $q = "SELECT UserId, UserUniqId, Email, Password, FirstName, LastName, PhotoUrl,
            		Role, Language, IsActive, SiteId, Created, Token 
        			FROM Users WHERE UserUniqId=?";
                    
            $s = $db->prepare($q);
            $s->bindParam(1, $userUniqId);
            
            $s->execute();
            
            $row = $s->fetch(PDO::FETCH_ASSOC);        
    
    		if($row){
    			return $row;
    		}
        
        } catch(PDOException $e){
            die('[User::GetByUserUniqId] PDO Error: '.$e->getMessage());
        }
        
	}
	
	// Gets a user for a specific userid
	public static function GetByUserId($userId){
		
		try{
        
            $db = DB::get();
            
            $q = "SELECT UserId, UserUniqId, Email, Password, FirstName, LastName, PhotoUrl,
            		Role, Language, IsActive, SiteId, Created, Token 
        			FROM Users WHERE UserId=?";
                    
            $s = $db->prepare($q);
            $s->bindParam(1, $userId);
            
            $s->execute();
            
            $row = $s->fetch(PDO::FETCH_ASSOC);        
    
    		if($row){
    			return $row;
    		}
        
        } catch(PDOException $e){
            die('[User::GetByUserId] PDO Error: '.$e->getMessage());
        }
    
	}
	
}

?>