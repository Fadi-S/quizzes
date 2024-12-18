# **Base URL**
`quizzes.stgsporting.com`


# **Base API**
`/api/v1`


# **Classes APIs**

### **Games** 

Base Path
class API: `/games`

1. **Create Token**
	- Method: *POST*
	- Endpoint: `/:game_id `
	- Response
```json
{
	"token": "<some-token>"
}
```

2. **Read game details**
	- Method: *GET*
	- Endpoint: `/:game_id`
	- Response
```json
{
	"name": "<game_name>",
	"picture": "<pic_url>",
	"data": {
		//some other data
	}
}
```

---

### **Groups**

Base Path
class API: `/groups`

1. **Create Group**
- Method: POST
- Endpoint: `/`
- headers: `{game_token}`
- Body:
  ```json
    {
      "name": "<grp_name>",
      "data": {
        //other data
      }
    }
  ```
     
- Response: `status code`

2. **Read game details**
	- Method: *GET*
	- Endpoint: `/:game_id`
	- Response
```json
  {
  	"name": "<game_name>",
  	"picture": "<pic_url>",
  	"data": {
  		//some other data
  	}
  }
```

3. **Delete Group**
	- Method: DELETE
	- Endpoint: `/:game_id `
	- headers: `{game_token}`
	- Response: `status code`
