# Simple Todo API
This is a simple Todo API

## Domain description
The domain is made up of two objects

- **TodoList**
- **Item**

A TodoList is made up of many Item, the both share the same set of State ('CREATED' => 'PENDING' => 'COMPLETED')
Initially a TodoList an all his Item are in CREATED state. Once one of the Item transits from CREATED to PENDING the parent TodoList also transition CREATE to PENDING. Once an item evolves to COMPLETED state the completionRate of the corresponding TodoList is automatically updated, all this automatics transistions are made using <b>Events by implementing and EventListenr on Item class</b>




