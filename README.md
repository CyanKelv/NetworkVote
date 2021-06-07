# NetworkVote

**Plugin to help sychronize vote reward claiming across network.** Database configurations can be found in `config.yml`.



## Basic Usage

The class instance can be initialized by importing the class `kelvin\networkvote\VotesAPI` and using the `getInstance()` function.
```php
$api = VotesAPI::getInstance();
```
### Get voting status
Player's vote status can be retrieved by using `hasVotedOn()` which accepts 3 arguments,
```php
/** @var \pocketmine\Player $player */
$api->hasVotedOn($player, $ip, $port);
```
The function `hasVotedOn()` will return one of the following constant depending on the output.
* `RET_NOT_VOTED` - Player has not voted on given IP and port
* `RET_VOTED` - Player has voted on given IP and port
* `RET_INVALID` - Player data is not loaded

### Update voting status
The `voteOn()` function can be called to update player vote status. The function accepts 3 arguments,
```php
/** @var \pocketmine\Player $player */
$api->voteOn($player, $ip, $port);
```

###Example usage:
```php
// If player voted on website but not on the server
if($api->hasVotedOn($player, "127.0.0.1", 19132) === VotesAPI::RET_NOT_VOTED){
    // Reward player here
    $player->sendMessage("Thank you for voting!");
    // Update player's vote status
    $api->voteOn($player, "127.0.0.1", 19132);
} else {
    $player->sendMessage("You had claimed your rewards!");
}
```

### Daily vote status reset
Voting status will be reset daily on 12:00:00AM EDT (Eastern Daylight Time)

#Drawback
* Only support voting synchronisation on one voting website. Eg. will not work when the vote rewards can be claimed multiple time on one server.
* Timezone of the voting website might differ from the timezone on server.
