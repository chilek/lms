#!/bin/bash

PATH="/bin:/sbin:/usr/bin:/usr/sbin"
DAEMON_NAME="lms-billingd"
DAEMON_COMMAND="./test3.php"

if [ $# -ne 1 ]; then
	echo "$0: syntax error!"
	exit 1
fi

function start() {
	PID=$(pidof ${DAEMON_NAME} 2>/dev/null)
	if [ $? -eq 0 ]; then
		echo "${DAEMON_NAME}: already started with PID: ${PID}!"
		return
	else
		${DAEMON_COMMAND}
		PID=$(pidof ${DAEMON_NAME} 2>/dev/null)
		if [ $? -eq 0 ]; then
			echo "${DAEMON_NAME}: started with PID: ${PID}."
			return
		fi
	fi
}

function stop() {
	PID=$(pidof ${DAEMON_NAME} 2>/dev/null)
	if [ $? -eq 0 ]; then
		kill -KILL ${PID}
		if [ $? -eq 0 ]; then
			echo "${DAEMON_NAME}: stopped PID: ${PID}."
		fi
	else
		echo "$0: ${DAEMON_NAME} is already stopped!"
	fi
}

function status() {
	PID=$(pidof ${DAEMON_NAME} 2>/dev/null)
	if [ $? -eq 0 ]; then
		echo "${DAEMON_NAME}: running with PID: ${PID}."
	else
		echo "${DAEMON_NAME}: stopped."
	fi
}

function reload() {
	PID=$(pidof ${DAEMON_NAME} 2>/dev/null)
	if [ $? -eq 0 ]; then
		echo "${DAEMON_NAME}: Reloading configuration for PID: ${PID}"
		kill -HUP ${PID}
	else
		echo "${DAEMON_NAME}: stopped"
	fi
}

case $1 in
	"start")
		start
		;;
	"stop")
		stop
		;;
	"restart")
		stop
		start
		;;
	"status")
		status
		;;
	"reload")
		reload
		;;
esac
