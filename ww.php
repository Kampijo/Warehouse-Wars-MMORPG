// Stage
blankImageSrc="icons/blank.gif";
monsterImageSrc="icons/face-devil-grin-24.png";
playerImageSrc="icons/face-cool-24.png";
boxImageSrc="icons/emblem-package-2-24.png";
wallImageSrc="icons/wall.jpeg";
demonImageSrc="icons/face-devil-grin-25.png";

function Stage(width, height){
	this.actors=[]; // all actors on this stage (monsters, player, boxes, ...)

	// the logical width and height of the stage
	this.width=width;
	this.height=height;

	// take a look at the value of these to understand why we capture them this way
	// an alternative would be to use 'new Image()'
	this.centerWidth = Math.floor(this.width/2);
	this.centerHeight = Math.floor(this.height/2);
}
// initialize an instance of the game
Stage.prototype.initialize=function(){

	for(i=0; i < this.height; i++){
		for(j=0; j < this.width; j++){
			var rand = Math.random();
			var type = "";
			if(i != this.centerHeight || j != this.centerWidth){
				if(i == 0 || j == 0 || i == this.height-1 || j == this.width-1){
					continue;
				} else if(rand < 0.005){
					this.addActor(new Demon(j, i, this));
				} else if(rand < 0.05){
					this.addActor(new Monster(j, i, this));
				} else if(rand < 0.3){
					this.addActor(new Box(j, i, this));
				} else {
					continue;
				}
			} 
		}
	}
}
Stage.prototype.renderTable=function(){

	var s='<table style="border-collapse:collapse;">';

	for(i=0; i < this.height; i++){
		s+="<tr>";
		for(j=0; j < this.width; j++){
			
			if(i == 0 || j == 0 || i == this.height-1 || j == this.width-1){
				s+="<td><img id="+i+","+j+" src="+wallImageSrc+" width=25 height=25 /></td>";
			} else {
				cell = this.getActor(j, i);
				if(cell instanceof Demon){
					s+="<td><img id="+i+","+j+" src="+demonImageSrc+" /></td>";
				} else if (cell instanceof Monster){
					s+="<td><img id="+i+","+j+" src="+monsterImageSrc+" /></td>";
				} else if (cell instanceof Box){
					s+="<td><img id="+i+","+j+" src="+boxImageSrc+" /></td>";
				} else if (cell instanceof Player){
					s+="<td><img id="+i+","+j+" src="+playerImageSrc+" /></td>";
				} else {
					s+="<td><img id="+i+","+j+" src="+blankImageSrc+" /></td>";
				}
			}
		}
		s+="</tr>";
	}
	s+="</table>";
	return s;
}

function Player(x, y, stage, id){
	this.x = x;
	this.y = y;
	this.stage = stage;
	this.id = id;
}
function Monster(x, y, stage){
	this.x = x;
	this.y = y;
	this.stage = stage;
}
function Demon(x, y, stage){
	this.x = x;
	this.y = y;
	this.stage = stage;
}
function Box(x, y, stage){
	this.x = x;
	this.y = y;
	this.stage = stage;
}
// random functions
function getRandomInt(min, max){
	return Math.floor(Math.random() * (max - min + 1)) + min;
}
function randomMove(){
	move = getRandomInt(0,1);
	
	if(Math.random() < 0.5){
		move = -move;
	}
	return move;
}

Monster.prototype.move=function(){
	xDir = randomMove();
	yDir = randomMove();
	nextCell = this.stage.getActor(this.x+xDir,this.y+yDir);
	if(this.stage.checkMovement(this.x, this.y)){ 
		if(this.stage.check(this.x+xDir, this.y+yDir) && (nextCell == null || nextCell instanceof Player)){
			Stage.setImage(this.x, this.y, blankImageSrc);
			this.x+=xDir;
			this.y+=yDir;
			Stage.setImage(this.x, this.y, monsterImageSrc);
			if(nextCell != null && nextCell instanceof Player){
				wss.broadcast(JSON.stringify({'id': nextCell.id, 'type':'death'}));
				console.log("GAME OVER!");
				this.stage.removeActor(nextCell);
			}
        }
	} else {
		this.stage.removeActor(this);
		Stage.setImage(this.x, this.y, blankImageSrc);				
        console.log("MONSTER KILLED at "+this.x+", "+this.y);
	}
}
Demon.prototype.move=function(){
	if(this.stage.checkMovement(this.x, this.y)){
		loop1:
		for(var i = -1; i <= 1; i++){
			loop2:
			for(var j = -1; j <= 1; j++){
				if(i == 0 && j == 0) continue;
				nextCell = this.stage.getActor(this.x+j, this.y+i);
				if(this.stage.check(this.x+j, this.y+i) && (nextCell == null || nextCell instanceof Player)){
					xDir = j;
					yDir = i;
					break loop1;
				}
			}
		}
		Stage.setImage(this.x, this.y, blankImageSrc);
		this.x+=xDir;
		this.y+=yDir;
		Stage.setImage(this.x, this.y, demonImageSrc);
		if(nextCell != null && nextCell instanceof Player){
			wss.broadcast(JSON.stringify({'id': nextCell.id, 'type':'death'}));
			console.log("GAME OVER!");
			this.stage.removeActor(nextCell);
		 }
	} else {
		this.stage.removeActor(this);
		Stage.setImage(this.x, this.y, blankImageSrc);
		console.log("MONSTER KILLED at "+this.x+", "+this.y);
	}
}


// Return the ID of a particular image, useful so we don't have to continually reconstruct IDs
Stage.getStageId=function(x,y){ return y+","+x; }
// Set the src of the image at stage location (x,y) to src
Stage.setImage=function(x, y, src){
	var data = JSON.stringify({'data':src, 'id':this.getStageId(x,y), 'type':'update'});
	wss.broadcast(data);
}
Stage.prototype.addActor=function(actor){
	this.actors.push(actor);
}

Stage.prototype.removeActor=function(actor){
	// Lookup javascript array manipulation (indexOf and splice).
	this.actors.splice(this.actors.indexOf(actor),1);
}
Stage.prototype.getPlayer=function(id){
	for(var i = 0; i < this.actors.length; i++){
		if(this.actors[i] instanceof Player && this.actors[i].id == id){
			return this.actors[i];
		}
	}
	return null;

}
Stage.prototype.removePlayer=function(id){
	for(var i = 0; i<this.actors.length;i++){
		if(this.actors[i] instanceof Player && this.actors[i].id == id){
			this.actors.splice(i,1);
		}
	}
}
// return the first actor at coordinates (x,y) return null if there is no such actor
// there should be only one actor at (x,y)!
Stage.prototype.getActor=function(x, y){
	for(var i = 0; i<this.actors.length;i++){
		if(this.actors[i].x==x && this.actors[i].y==y){
			return this.actors[i];
		}
	}
	return null;
}

Stage.prototype.check=function(y, x){
	if (x <= 0 || y <= 0) return false;
	if (x >= this.width-1 || y >= this.height-1) return false;
	return true;
}
Stage.prototype.checkMovement=function(x,y){
	for(var i = -1; i <= 1; i++){
		for(var j = -1; j <= 1; j++){
			if(i !=0 || j != 0){
				if(this.check(x+i,y+j) && (this.getActor(x+i,y+j) == null || 
					this.getActor(x+i, y+j) instanceof Player)){
					return true;
				}
			}
		}
	} 
	return false;	
}

// Take one step in the animation of the game.  
Stage.prototype.step=function(){
	for(var i=0;i<this.actors.length;i++){
		var actor = this.actors[i];
		if(actor instanceof Box){
		    Stage.setImage(actor.x, actor.y,boxImageSrc);
		} else if(actor instanceof Monster){
			Stage.setImage(actor.x,actor.y,monsterImageSrc);
		} else if(actor instanceof Demon){
			Stage.setImage(actor.x, actor.y,demonImageSrc);
		} else {
			Stage.setImage(actor.x,actor.y,playerImageSrc);
		}
	}
}


Stage.prototype.moveMonsters=function(){
	for(var i=0;i<this.actors.length;i++){
		if(this.actors[i] instanceof Monster || this.actors[i] instanceof Demon){
			this.actors[i].move();	
		}
	}
}
Player.prototype.move=function(direction){
	xDir = 0;
	yDir = 0;
	switch (direction){
		case 'N':
			if(this.stage.check(this.y-1, this.x)){
				yDir--;
			}
			break;
		case 'S':
			if(this.stage.check(this.y+1, this.x)){
				yDir++;
			}
			break;
		case 'W':
			if(this.stage.check(this.y, this.x-1)){
				xDir--;
			}
			break;
		case 'E':
			if(this.stage.check(this.y, this.x+1)){
				xDir++;
			}
			break;
		case 'NW':
			if(this.stage.check(this.y-1, this.x-1)){
				xDir--;
				yDir--;
			}
			break;
		case 'NE':
			if(this.stage.check(this.y-1, this.x+1)){
				xDir++;
				yDir--;
			}
			break;
		case 'SW':
			if(this.stage.check(this.y+1, this.x-1)){
				xDir--;
				yDir++;
			}
			break;
		case 'SE':
			if(this.stage.check(this.y+1, this.x+1)){
				xDir++;
				yDir++;
			}
			break;
	}
	if(xDir != 0 || yDir != 0){
		if(this.stage.moveBoxes(this.x+xDir, this.y+yDir, direction)){
			Stage.setImage(this.x, this.y, blankImageSrc);
			this.x+=xDir;
			this.y+=yDir;
		//	Stage.setImage(this.x, this.y, playerImageSrc);
		}	 
	}	
}

Stage.prototype.movePlayer=function(direction, id){
	for(var i = 0; i<this.actors.length;i++){
		if(this.actors[i] instanceof Player && this.actors[i].id == id){
			var playerIndex = i;
			var xDir = 0;
			var yDir = 0;
			break;
		}
	}
	this.actors[playerIndex].move(direction);
	
}
Box.prototype.move=function(direction){
	var xDir = 0;
	var yDir = 0;
		
	switch (direction){
			case 'N':
				if(this.stage.check(this.y-1, this.x)){
					yDir--;
				}
				break;
			case 'S':
				if(this.stage.check(this.y+1, this.x)){
					yDir++;
				}
				break;
			case 'W':
				if(this.stage.check(this.y, this.x-1)){
					xDir--;
				}
				break;
			case 'E':
				if(this.stage.check(this.y, this.x+1)){
					xDir++;
				}
				break;
			case 'NW':
				if(this.stage.check(this.y-1, this.x-1)){
					xDir--;
					yDir--;
				}
				break;
			case 'NE':
				if(this.stage.check(this.y-1, this.x+1)){
					xDir++;
					yDir--;
				}
				break;
			case 'SW':
				if(this.stage.check(this.y+1, this.x-1)){
					xDir--;
					yDir++;
				}
				break;
			case 'SE':
				if(this.stage.check(this.y+1, this.x+1)){
					xDir++;
					yDir++;
				}
				break;
			}
	if(xDir != 0 || yDir != 0){
		var actor = this.stage.getActor(this.x+xDir, this.y+yDir);
		if(actor == null) {
			this.x+=xDir;
			this.y+=yDir;
			return true;
		}
		if(actor instanceof Monster){
			return false;
		}
		if(actor.move(direction)){
			Stage.setImage(this.x, this.y, blankImageSrc);
			this.x+=xDir;
			this.y+=yDir;
			Stage.setImage(this.x, this.y, boxImageSrc);
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
	
}

Stage.prototype.moveBoxes=function(x, y, direction){
	var actor = this.getActor(x, y);
	var index = this.actors.indexOf(actor);
	if(actor == null) return true;
	if(actor instanceof Monster) return false;
	return this.actors[index].move(direction);
}
updateInterval = null;
function setupGame(){
	stage=new Stage(20,20,"stage");
	stage.initialize();
}
function startGame(){
	if(updateInterval == null){
		updateInterval = setInterval(step, 100);
	}
}
function pauseGame(){
	clearInterval(updateInterval);
}
function resetGame(){
	stage=null;
	updateInterval=null;
}
counter = 0;
function step(){
	stage.step();
	counter++;
	if(counter == 10){
		stage.moveMonsters();
		counter = 0;
	}
}

setupGame();
startGame();
///////////////////////// END //////////////////////////////////

var world = {};
world["status"] = "";
world["users"] = [];

WebSocketServer = require('ws').Server
   ,wss = new WebSocketServer({port: 10551, clientTracking: true});
	
wss.on('close', function() {
    console.log('Disconnected');
	world['status'] = 'offline';
});

wss.broadcast = function(message){
	for(let ws of this.clients){ 
		ws.send(message); 
	}
}
wss.on('connection', function(ws) {
	var userID = ws.upgradeReq.url.substring(1);
	world["users"].push(userID);
	wss.broadcast(JSON.stringify({'type': 'users', 'users': world["users"]}));
	stage.addActor(new Player(stage.centerWidth, stage.centerHeight, stage, userID));
	ws.send(JSON.stringify({'id':'stage', 'data': stage.renderTable(), 'type':'render'}));
	ws.on('message', function(message) {
		var playerMove = JSON.parse(message);
		console.log(playerMove.direction);
		var player = stage.getPlayer(playerMove.id);
		if(player){
			var prevCoord = Stage.getStageId(player.x, player.y);
			stage.movePlayer(playerMove.direction, playerMove.id);
			wss.broadcast(JSON.stringify({'type': 'player', 'id': playerMove.id, 'prevCoord': prevCoord, 'coord': Stage.getStageId(player.x, player.y)}));
		}
	});
	ws.on('close', function(message){
		console.log("DISCONNECTING PLAYER FROM BOARD");
		var userID = ws.upgradeReq.url.substring(1);
		stage.removePlayer(userID);
		world["users"].splice(world["users"].indexOf(userID), 1);
		wss.broadcast(JSON.stringify({'type': 'users', 'users':world["users"]}));
	});
});
