#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include "bitcoinconsensus.h"
#include <signal.h>
#include <errno.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/un.h>


int getLine(const char *buf, int bufSize, unsigned char out[], int outSize) {
    int i = 0;
    int j = 0;
    while (buf[i] != '\n' && buf[i] != '\0' && j < outSize && i+1 < bufSize) {
        char b; // must use int with sscanf()
        sscanf(&buf[i], "%2hhx", &b);
        out[j] = b;
        i += 2;
        j += 1;
    }
    return j;
}


void printArray(unsigned char a[], int n) {
    int i;
    for(i = 0; i < n; i++) {
        printf("%02x", a[i]);
    }
}

int parse_file(FILE* fp, unsigned char scriptPubKey[], unsigned int* scriptPubKeyLen, 
        unsigned char txTo[], unsigned int* txToLen, unsigned int* nIn, unsigned int* flags) {
    int lineSize = 20000;
    char line[lineSize];
    int n = fread(scriptPubKey, 1, 10000, fp);
    *scriptPubKeyLen = n;

    strcpy(line, "0100000001a38021e92bd97f43857328a337df14a084bb9d87e9dd82e607e96170e13c103a0000000000ffffffff0100f2052a010000001976a9146aeffd5d1dcc7f85a431d9d5798e2e13c8bf847a88ac00000000\n");
    int end = getLine(line, lineSize, txTo, 10000);
    *txToLen = end;

    return 0;
}

/*int get_script(FILE* fp, unsigned char script[], unsigned int script) {*/
    /*int lineSize = 20000;*/
    /*char line[lineSize];*/
    /*fgets(line, sizeof(line), fp);*/
/*}*/

void exec(char* cmd, char *buf) {
    FILE* pipe = popen(cmd, "r");
    if (!pipe) {
        return;
    }
    /*while(!feof(pipe)) {*/
        /*if(fgets(buffer, 128, pipe) != NULL)*/
            /*result += buffer;*/
    /*}*/
    fgets(buf, 2048, pipe);
    pclose(pipe);
    return;
}


void execSocket(char* sock_path, char* sendFile, char *outbuf) {
    int s, t, len;
    struct sockaddr_un remote;
    char str[100];

    if ((s = socket(AF_UNIX, SOCK_STREAM, 0)) == -1) {
        perror("socket");
        exit(1);
    }

    remote.sun_family = AF_UNIX;
    strcpy(remote.sun_path, sock_path);
    len = strlen(remote.sun_path) + sizeof(remote.sun_family);
    if (connect(s, (struct sockaddr *)&remote, len) == -1) {
        perror("connect");
        exit(1);
    }

    send(s, sendFile, strlen(sendFile), 0);

    t=recv(s, outbuf, 2048, MSG_WAITALL);
    str[t] = '\0';

    close(s);
}

int main(int argc, char *argv[]) {
    FILE *fp;
    fp = fopen(argv[1], "rb");

    unsigned char scriptPubKey[20000];
    unsigned int scriptPubKeyLen;
    unsigned char txTo[20000];
    unsigned int txToLen;
    unsigned int nIn;
    unsigned int flags;
    parse_file(fp, scriptPubKey, &scriptPubKeyLen, txTo, &txToLen, &nIn, &flags);
    nIn =0;
    flags = 0;

    bitcoinconsensus_error err = bitcoinconsensus_ERR_OK;
    unsigned char stack[1024];
    unsigned int stackSize = 0;

    const int CALLSTRSIZE = 256;
    char callstr[CALLSTRSIZE];

    char buffer[2048];
    if (argc >= 3 && (atoi(argv[2])==1 || atoi(argv[2])==3)) {
        snprintf(callstr, CALLSTRSIZE, "node valid_script_stack.js %s", argv[1]);
        exec(callstr, buffer);
    } else {
        snprintf(callstr, CALLSTRSIZE, "%s", argv[1]);
        execSocket(argv[2], callstr, buffer);
    }

    int ret = bitcoinconsensus_verify_script_stack(scriptPubKey, scriptPubKeyLen, txTo, txToLen, nIn, flags, &err, stack, &stackSize);
    printArray(stack, stackSize);
    printf("\n");

    char bitcoreStack[1024];
    int bitcoreStackLen = getLine(buffer, 2048, bitcoreStack, 1024);
    printArray(bitcoreStack, bitcoreStackLen);
    printf("\n");

    /*printf("%u\n", stackSize);*/
    if (memcmp(bitcoreStack, stack, stackSize) != 0) {
        if (argc >= 3 && (atoi(argv[2])==2 || atoi(argv[2])==3)) {
            printf("differ");
        }
        raise(SIGSEGV);
    }

    return ret;
}
