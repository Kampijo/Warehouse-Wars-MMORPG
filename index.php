var express = require('express');
var bodyParser = require('body-parser');
var app = express();
var MongoClient = require('mongodb').MongoClient;
const path = require('path');
function connect(){
	MongoClient.connect("mongodb://lopeznyg:13779@mcsdb.utm.utoronto.ca/lopeznyg_309", function(err, db) {
  		if(!err) {  
			db.createCollection('users', function(err, collection) {});
			db.createCollection('scores', function(err, collection) {});
			users = db.collection('users');
			scores = db.collection('scores');
  		}
	});
}
connect();
var port = 10550;
app.use(express.static(path.join(__dirname,'static_files')));
app.use(bodyParser.urlencoded({extended: true}));
app.use(bodyParser.json());

app.get('/', function (req, res){
	res.sendFile(__dirname+"/"+"index.html");
});

app.get('/authenticate', function (req, res){
	connect();
	var auth = req.get("authorization");
	var credentials = new Buffer(auth.split(" ").pop(), "base64").toString("ascii").split(":");
	users.findOne({"_id": credentials[0], "password": credentials[1]}, function(err, docs){
		if(!err && docs){
			response = {
				"status": "Success!"
			}
			res.status(200).json(response);
		} else {
			response = {
				"status": "Incorrect credentials!"
			};
			res.status(401).json(response);
		}
	});
});

app.get('/hiScores', function (req, res){
	connect();
	scores.find({}, {'_id':0,'user':1, 'score':1}).sort({"score":-1}).limit(10).toArray(function(err, docs){
		if(!err){
			if(!docs) docs=[];
			response = {
				"status": "Success!",
				"response": docs
			};
			res.status(200).json(response);
		}
	});
});

app.get('/:user/hiScores', function (req, res){
	connect();
	var user = req.params.user;
	var auth = req.get("authorization");
    var credentials = new Buffer(auth.split(" ").pop(), "base64").toString("ascii").split(":");	
	if(credentials[0] == user){
    	users.findOne({"_id": credentials[0], "password": credentials[1]}, function(err, docs){
			if(!err && docs){
        		response = {
       	        	"status": "Success!",
					"response": []
            	}
 	        	scores.find({"user": credentials[0]}, {"_id":0,"score":1}).toArray(function(err, docs){
					scores = [];
					for(var i = 0; i < docs.length; i++){
						scores.push(docs[i].score);
					}
					response["response"] = scores;
					res.status(200).json(response);
				});
        	} else {
            	response = {
                	"status": "Incorrect credentials!"
            	};
            	res.status(401).json(response);
        	}
   		});
	} else {
		response = {
			"status": "Incorrect credentials!"
		};
		res.status(401).json(response);
	}	
});

app.put('/register', function (req, res){
	connect();
	var user = req.body.user;
	var password = req.body.password;	
	response = {};
	users.insertOne({"_id": user, "password":password}, function(err, result){
		if(!err){
			response["status"]="Success!";
			res.status(200).json(response);	
		} else {
			response["status"]="User already exists!";
			res.status(403).json(response);
		}
	});
});

app.put('/insertScore', function(req, res){
	connect();
	var score = req.body.score;
    var auth = req.get("authorization");
    var credentials = new Buffer(auth.split(" ").pop(), "base64").toString("ascii").split(":");
    users.findOne({"_id": credentials[0], "password": credentials[1]}, function(err, docs){
         if(!err && docs){
             response = {
                 "status": "Success!"
             }
			 scores.insertOne({"user": credentials[0], "score":score});
             res.status(200).json(response);
         } else {
             response = {
                 "status": "Incorrect credentials!"
             };
             res.status(401).json(response);
         }
     });
});

app.post('/update', function(req, res){
	connect();
	var password = req.body.password;
	var auth = req.get("authorization");
    var credentials = new Buffer(auth.split(" ").pop(), "base64").toString("ascii").split(":");
    users.findOne({"_id": credentials[0], "password": credentials[1]}, function(err, docs){
          if(!err && docs){
              response = {
                  "status": "Success!"
              }
              users.updateOne({"_id": credentials[0], "password":credentials[1]}, 
				{$set:{"password":password}});
              res.status(200).json(response);
          } else {
              response = {
                  "status": "Incorrect credentials!"
              };
              res.status(401).json(response);
          }
      });
});

app.delete("/delete/:user", function(req, res){
	connect();
	var user = req.params.user;
    var auth = req.get("authorization");
    var credentials = new Buffer(auth.split(" ").pop(), "base64").toString("ascii").split(":");
    users.findOne({"_id": credentials[0], "password": credentials[1]}, function(err, docs){
          if(!err && docs){
              response = {
                  "status": "Success!"
              }
              users.deleteOne({"_id": credentials[0], "password":credentials[1]});
              res.status(200).json(response);
          } else {
              response = {
                  "status": "Incorrect credentials!"
              };
              res.status(401).json(response);
          }
      });
});
	
app.listen(port, function(){
	console.log("Listening on port "+port);
});
