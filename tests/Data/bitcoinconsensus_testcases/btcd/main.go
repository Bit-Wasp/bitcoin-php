package main

import (
	"bufio"
	"encoding/hex"
	"fmt"
	"log"
	"os"
	"strconv"

	"github.com/btcsuite/btcd/txscript"
	"github.com/btcsuite/btcutil"
)

func main() {
	if len(os.Args) < 2 {
		log.Println("Not enough arguments")
		log.Println("Usage:", os.Args[0], " FILE")
		os.Exit(0)
	}
	log.SetFlags(log.LstdFlags | log.Lshortfile)

	file, err := os.Open(os.Args[1])
	if err != nil {
		log.Fatal(err)
	}
	defer file.Close()

	scanner := bufio.NewScanner(file)
	i := 0
	lines := make([]string, 5)
	for scanner.Scan() {
		lines[i] = scanner.Text()
		i += 1
	}

	if err := scanner.Err(); err != nil {
		log.Fatal(err)
	}

	scriptPubkey, err := hex.DecodeString(lines[0])
	if err != nil {
		log.Fatal(err)
	}

	txToBytes, err := hex.DecodeString(lines[1])
	if err != nil {
		log.Fatal(err)
	}

	txTo, err := btcutil.NewTxFromBytes(txToBytes)
	if err != nil {
		log.Fatal(err)
	}
	txToMsg := txTo.MsgTx()

	nIn, err := strconv.Atoi(lines[2])
	if err != nil {
		log.Fatal(err)
	}

	script, err := txscript.NewEngine(scriptPubkey, txToMsg, int(nIn), 0)
	err = script.Execute()
	if err != nil {
		fmt.Println("0")
		fmt.Println(err.Error())
	} else {
		fmt.Println("1")
	}
}
