#!/bin/bash

echo $(pwd)
startServer="php ../app/console gos:websocket:server"

echo -e "Running: \n$ $startServer"
pullCmdRun=$($startServer)