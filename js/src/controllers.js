
var budgetApp = angular.module('budgetApp',['ngRoute','ui.bootstrap'], function($httpProvider) {

});
budgetApp.config(['$routeProvider', 
	function ($routeProvider) {
		$routeProvider
			.when('/',
				{
					controller: 'MainController',
					templateUrl: 'views/main.html'
				})
			.when('/login',
				{
					controller: 'LoginController',
					templateUrl: 'views/login.html'
				})
			.when('/register',
				{
					controller: 'RegisterController',
					templateUrl: 'views/register.html'
				})
			.when('/accounts',
				{
					controller: 'MainController',
					templateUrl: 'views/accounts.html'
				})
			.when('/budgets',
				{
					controller: 'BudgetController',
					templateUrl: 'views/budgets.html'
				})
			.when('/categories',
				{
					controller: 'CategoryController',
					templateUrl: 'views/categories.html'
				})
			.when('/accounts/:account_id',
				{
					controller: 'AccountViewController',
					templateUrl: 'views/accountDetails.html',

				})
			.when('/upload_history',
				{
					controller: 'UploadController',
					templateUrl: 'views/uploadHistory.html',

				})
			.otherwise({ redirectTo: '/'});
	}
]);

budgetApp.controller('UploadController', function ($scope, budgetModel, $location) {

    //============== DRAG & DROP =============
    // source for drag&drop: http://www.webappers.com/2011/09/28/drag-drop-file-upload-with-html5-javascript/
    var dropbox = document.getElementById("dropbox")
    $scope.dropText = 'Drop files here...'

    // init event handlers
    function dragEnterLeave(evt) {
        evt.stopPropagation()
        evt.preventDefault()
        $scope.$apply(function(){
            $scope.dropText = 'Drop files here...'
            $scope.dropClass = ''
        })
    }
    dropbox.addEventListener("dragenter", dragEnterLeave, false)
    dropbox.addEventListener("dragleave", dragEnterLeave, false)
    dropbox.addEventListener("dragover", function(evt) {
        evt.stopPropagation()
        evt.preventDefault()
        var clazz = 'not-available'
        var ok = evt.dataTransfer && evt.dataTransfer.types && evt.dataTransfer.types.indexOf('Files') >= 0
        $scope.$apply(function(){
            $scope.dropText = ok ? 'Drop files here...' : 'Only files are allowed!'
            $scope.dropClass = ok ? 'over' : 'not-available'
        })
    }, false)
    dropbox.addEventListener("drop", function(evt) {
        console.log('drop evt:', JSON.parse(JSON.stringify(evt.dataTransfer)))
        evt.stopPropagation()
        evt.preventDefault()
        $scope.$apply(function(){
            $scope.dropText = 'Drop files here...'
            $scope.dropClass = ''
        })
        var files = evt.dataTransfer.files
        if (files.length > 0) {
            $scope.$apply(function(){
                $scope.files = []
                for (var i = 0; i < files.length; i++) {
                    $scope.files.push(files[i])
                }
            })
        }
    }, false)
    //============== DRAG & DROP =============

    $scope.setFiles = function(element) {
    $scope.$apply(function($scope) {
      console.log('files:', element.files);
      // Turn the FileList object into an Array
        $scope.files = []
        for (var i = 0; i < element.files.length; i++) {
          $scope.files.push(element.files[i])
        }
      $scope.progressVisible = false
      });
    };

    $scope.uploadFile = function() {
        var fd = new FormData()
        for (var i in $scope.files) {
            fd.append("uploadedFile", $scope.files[i])
        }
        var xhr = new XMLHttpRequest()
        xhr.upload.addEventListener("progress", uploadProgress, false)
        xhr.addEventListener("load", uploadComplete, false)
        xhr.addEventListener("error", uploadFailed, false)
        xhr.addEventListener("abort", uploadCanceled, false)
        xhr.open("POST", "fileupload.php")
        $scope.progressVisible = true
        xhr.send(fd)
    }

    function uploadProgress(evt) {
        $scope.$apply(function(){
            if (evt.lengthComputable) {
                $scope.progress = Math.round(evt.loaded * 100 / evt.total)
            } else {
                $scope.progress = 'unable to compute'
            }
        })
    }

    function uploadComplete(evt) {
        /* This event is raised when the server send back a response */
        alert(evt.target.responseText)
    }

    function uploadFailed(evt) {
        alert("There was an error attempting to upload the file.")
    }

    function uploadCanceled(evt) {
        $scope.$apply(function(){
            $scope.progressVisible = false
        })
        alert("The upload has been canceled by the user or the browser dropped the connection.")
    }
});

budgetApp.controller('RegisterController', function ($scope, authModel, $location) {
	$scope.newUser = {};
	$scope.confirm_password = "";

	$scope.createUser = function () {
		if(typeof $scope.newUser.password != "undefined" && $scope.newUser.password != "" && $scope.newUser.password == $scope.confirm_password) {
			$scope.newUser.register = true;
			authModel.register($scope.newUser).then( function(data) {
				if ((typeof data === 'object') && (typeof data.username != "undefined")) {
					$location.path('#/');
				} else {
					console.log("Login failed");
				}
			});
		}
	};
});

budgetApp.controller('LoginController', function ($scope, authModel, $location) {
	$scope.username = "";
	$scope.password = "";

	$scope.login = function () {
		console.log("Do login: " + $scope.username);

		authModel.login($scope.username, $scope.password).then( function(data) {
			if ((typeof data === 'object') && (typeof data.username != "undefined")) {
				console.log("Login successful");
				console.log(data);
				$location.path('#/');
			} else {
				console.log("Login failed");
			}
		});
	}

});

budgetApp.controller('BudgetController', function ($scope, budgetModel, $routeParams) {
	$scope.budgets = [];
	$scope.categories = [];
	$scope.newTransfer = {};
	$scope.isCollapsed = true;
	$scope.formButtonText = "Show Transfer Form";

	budgetModel.getCategories().then( function(data) {
		$scope.categories = data;
	});

	budgetModel.getBudgets().then( function(data) {
		$scope.budgets = data;
	});

	// date picker stuff

  	$scope.today = function() {
   		 $scope.newTransfer.t_date = new Date();
  	};
  	$scope.today();
	$scope.showWeeks = false;
	$scope.dateOptions = {
	
	};
	$scope.format = 'MM-dd-yyyy';
	$scope.open = function($event) {
		$event.preventDefault();
		$event.stopPropagation();
		$scope.opened = true;
	};

	$scope.toggleFormButton = function () {
		$scope.isCollapsed = !$scope.isCollapsed;
		if ($scope.isCollapsed) {
			$scope.formButtonText = "Show Transfer Form";
		} else {
			$scope.formButtonText = "Hide Transfer Form";
		}
	};
	$scope.createTransfer = function() {
		// should do validation...

		budgetModel.createTransfer($scope.newTransfer).then( function(data) {
			budgetModel.getBudgets().then( function(data) {
				$scope.budgets = data;
			});
			$scope.newTransfer = {};
		});
	};

});

budgetApp.controller('CategoryController', function ($scope, budgetModel, $routeParams) {
	$scope.category = {};
	$scope.categories = [];
	$scope.editCategory = {};
	$scope.editFormIsCollapsed = true;
	$scope.toggleButtonText = "Show Category Form";

	budgetModel.getCategories().then( function(data) {
		$scope.categories = data;
	});

	$scope.toggleFormButton = function () {
		$scope.editFormIsCollapsed = !$scope.editFormIsCollapsed;
		if ($scope.editFormIsCollapsed) {
			$scope.toggleButtonText = "Show Category Form";
		} else {
			$scope.toggleButtonText = "Hide Category Form";
		}
	};
	$scope.createCategory = function () {
		budgetModel.createCategory($scope.editCategory).then( function(data) {

			$scope.newCategory = data; // not sure

			budgetModel.getCategories().then( function(data) {
				$scope.categories = data;
			});
			$scope.editCategory = {};
		});
	};

});
	
budgetApp.controller('AccountViewController', function ($scope, budgetModel, $routeParams) {

	$scope.account = {};
	$scope.transactions = [];
	$scope.isCollapsed = true;
	$scope.editTransaction = {};
	$scope.transactionButtonText = "Show Transaction Form";
	$scope.categories = [];

	$scope.transactionType = "Withdrawal";

	$scope.changeTransactionType = function () {
		if ($scope.transactionType == "Withdrawal") {
			$scope.transactionType = "Deposit";
		} else {
			$scope.transactionType = "Withdrawal";
		}
	};


	// date picker stuff

  	$scope.today = function() {
   		 $scope.editTransaction.t_date = new Date();
  	};
  	$scope.today();
	$scope.showWeeks = false;
	$scope.dateOptions = {
	
	};
	$scope.format = 'MM-dd-yyyy';
	$scope.open = function($event) {
		$event.preventDefault();
		$event.stopPropagation();
		$scope.opened = true;
	};

	budgetModel.getCategories().then( function(data) {
		$scope.categories = data;
	});
	budgetModel.getAccountById($routeParams.account_id).then( function(data) {
		$scope.account = data[0];
		budgetModel.getTransactionsForAccount($routeParams.account_id).then( function(data) {
			$scope.transactions = data;
		});
	});
	$scope.toggleFormButton = function () {
		$scope.isCollapsed = !$scope.isCollapsed;
		if ($scope.isCollapsed) {
			$scope.transactionButtonText = "Show Transaction Form";
		} else {
			$scope.transactionButtonText = "Hide Transaction Form";
		}
	};
	$scope.createTransaction = function () {
		$scope.editTransaction.account_id = $scope.account.id;
		if ($scope.transactionType == "Withdrawal") {
			$scope.editTransaction.amount = $scope.editTransaction.amount * -1;
		}
		budgetModel.createTransaction($scope.editTransaction).then( function(data) {

			$scope.newTransactions = data; // not sure

			budgetModel.getTransactionsForAccount($scope.account.id).then( function(data) {
				$scope.transactions = data;
			});
			$scope.editTransaction = {};
		});
		

	};
	$scope.modifyTransaction = function(t) {
		$scope.editTransaction = JSON.parse(JSON.stringify(t));
		if ($scope.editTransaction.amount < 0) {
			$scope.editTransaction.amount *= -1;
			$scope.transactionType = "Withdrawal";
		} else {
			$scope.transactionType = "Deposit";
		}

		if ($scope.isCollapsed) {
			$scope.toggleFormButton();
		}
		console.log($scope.editTransaction);
	};
});

budgetApp.controller('MainController', function ($scope, $routeParams, budgetModel) {
	$scope.username = "";
	$scope.selectedAccount = {};
	$scope.accounts = [];
	$scope.transactions = [];

	$scope.editAccount = {};
	$scope.editTransaction = {};

	$scope.isCollapsed = true;

	$scope.showAccount = function () {
		$scope.dates.push( new Date());
		console.log($scope.dates);

	};

	$scope.createAccount = function (name) {
		budgetModel.createAccount(name).then( function(data) {
			$scope.accounts = data;
		});

	};		

	$scope.selectAccount = function (id) {
		$scope.somerandomshit = "funny";

		budgetModel.getAccountById(id).then( function(data) {
			$scope.selectedAccount = data[0];
			console.log("Inside selectAccount:");
			console.log( $scope.selectedAccount);
			budgetModel.getTransactionsForAccount(id).then( function(data) {
				$scope.transactions = data;
				console.log($scope.transactions);
			});
		});
	};


	budgetModel.getIdentity().then( function(data) {
		$scope.username = data.username;
	});
	budgetModel.getAccounts().then( function(data) {
		$scope.accounts = data;
	});

	$scope.loggedout = function () {
		return ($scope.username == "");
	};

});

budgetApp.factory('authModel', function ($http) {
	return {
		login: function(uid, pwd) {
			return $http.put('model/auth.php', {data: {username: uid, password: pwd}}).then(function(result) {
				return result.data;
			});
		},
		register: function(u) {
			console.log(u);
			return $http.put('model/auth.php', {data: u}).then(function(result) {
				return result.data;
			});
		},
		getIdentity: function() {
			return $http.get('model/auth.php').then(function(result) {
				return result.data;
  			});
		}
  };
});

budgetApp.factory('budgetModel', function ($http) {

  return {

      getAccounts: function() {
			return $http.get('model/accounts.php').then(function(result) {
				return result.data;
			});
      },
      getBudgets: function() {
			return $http.get('model/budgets.php').then(function(result) {
				return result.data;
			});
      },
      getAccountById: function(id) {
			return $http.get('model/accounts.php', {params: {id: id}}).then(function(result) {
				return result.data;
			});
      },
      createAccount: function(name) {
			return $http.put('model/accounts.php', {data: {name: name}}).then(function(result) {
				return result.data;
			});
      },
      createTransaction: function(t) {
			return $http.put('model/transactions.php', {data: t }).then(function(result) {
				return result.data;
			});
      },
      createTransfer: function(t) {
			return $http.put('model/budgets.php', {data: t }).then(function(result) {
				return result.data;
			});
      },
      createCategory: function(c) {
			return $http.put('model/categories.php', {data: c }).then(function(result) {
				return result.data;
			});
      },
      getCategories: function() {
			return $http.get('model/categories.php').then(function(result) {
				return result.data;
			});
      },
      getTransactionsForAccount: function(id) {
			return $http.get('model/transactions.php', {params: {account_id: id}}).then(function(result) {
				var c = result.data.length -1;
				var total = new Number(result.data[c].balance);

				for (var i = c; i>= 0; i--) {
					total = total + new Number(result.data[i].amount);
					result.data[i].balance = total;
				}
				return result.data;
			});
      },
      getIdentity: function() {
			return $http.get('model/auth.php').then(function(result) {
				return result.data;
			});
      },
  };

});

