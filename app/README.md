# Application features
The application is composed by a backend written in Node.js and a frontend written in PHP. Both backend and frontend are containerized using Docker. There are two versions of them, which are better described in the following paragraphs.

## Version 0.1
In the first version of the application, the **backend** has two endpoints:
- `/`: the main endpoint, which returns the welcome message `Hello, World!`
- `/hostname`: returns the name of the host that is serving the backend itself

The **frontend**, instead, shows on the page some useful info like the name of the host that is serving it and the values returned by the backend endpoints.

## Version 0.2
In the second version of the application **backend** : 
- The port on which it is listening is no longer hardcoded, but it's configured by `config.js` file. 
- The main endpoint welcome message now says `Hello, K8s!`.
- One more enpoint is added, `/auth`, which authenticates the frontend with some credentials.

Similarly, the new version of the **frontend** will show the new welcome message and the result of the authentication, with all the info that showed in the previous version.