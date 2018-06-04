package com.ffan.ucenter.utils;

/*
 * Project: Alarm
 * Module: Qalarm.java
 *  功能：调用者直接写数据到内存，调用者会通过时间和内存占用量两个维度选择刷盘时机
 *
 *  依赖：依赖gson
 *
 *  性能：
 *  (1) 单线程
 *    单条消息大小：400B
 *    磁盘io util： --> 0%，瞬时 8-9%
 *    QPS: 22045条/sec
 *  (2) 多线程
 *    单条消息大小：400B
 *    磁盘io util： --> 0%，瞬时 8-9%
 *    QPS: 20411条/sec
 * Copyright @2015 ttyongche Technology Co. Ltd. All Rights Reserved.
 *
 * Author: willas, <chenfei@ttyongche.com>
 *
 * Contributor: wangneng@ttyongche.com
 */


import com.google.gson.Gson;

import java.io.FileWriter;
import java.net.InetAddress;
import java.net.UnknownHostException;
import java.util.concurrent.BlockingQueue;
import java.util.concurrent.LinkedBlockingDeque;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.locks.ReentrantLock;

public class Qalarm {
    private static final String LOG_PATH = "/var/wd/wrs/logs/alarm/";
    private static final String LOG_NAME = "alarm.log";
    private static final int FLUSH_INTERVAL = 100;      //100ms 刷入一次磁盘
    private static final int  FLUSH_MAX_LINE = 1000; // 积攒的最大条数
    private static final int QUEUE_MAX_SIZE = 10000; //队列中最多缓存的日志条数

    private static Gson gson = new Gson();
    private static  BlockingQueue<String> logQueue = new LinkedBlockingDeque<String>();
    private static volatile long lastTime = 0;
    private static ReentrantLock lock = new ReentrantLock();

    //在停止jvm前打印队列中余留的日志信息
    static {
        Thread t = new Thread(new Runnable() {
            @Override
            public void run() {
                writeToFile();
            }
        });
        Runtime.getRuntime().addShutdownHook(t);
    }

    private Qalarm() {
    }

    public static boolean send(String project, String module, String code, String msg) {
        return send(project, module, code, msg, "", getLocalIP(), getCallPoint());
    }

    public static boolean send(String project, String module, String code, String msg, String clientIP) {
        return send(project, module, code, msg, clientIP, getLocalIP(), getCallPoint());
    }

    public static boolean send(String project, String module, String code, String msg, String clientIP, String serverIP, String script) {
        if ( logQueue.size() >  QUEUE_MAX_SIZE ) {
            //TODO：可考虑按照一定的间隔打日志，记录队列情况
            return false;
        }

        AlarmData data = new AlarmData();
        data.project   = project;
        data.module    = module;
        data.code      = code;
        data.message   = msg;
        data.client_ip = clientIP;
        data.server_ip = serverIP;
        data.script    = script;

        data.time      = Long.toString(System.currentTimeMillis() / 1000);
        data.env       = "prod";
        String msgJson = gson.toJson(data);

        logQueue.add(msgJson);
        if (isNeedFlush(msg)) {
            return writeToFile();
        }

        return true;
    }

    private static boolean isNeedFlush(String msgs) {
        long currTime = System.currentTimeMillis();
        if ( logQueue.size() >= FLUSH_MAX_LINE || currTime - lastTime > FLUSH_INTERVAL ) {
            lastTime = currTime;
            return true;
        }

        return false;
    }

    private static String getCallPoint() {
        StackTraceElement stack[] = Thread.currentThread().getStackTrace();
        return stack[3].toString();
    }

    private static String getLocalIP()  {
        String localIP = null;
        try {
            localIP = InetAddress.getLocalHost().getHostName();
        } catch (UnknownHostException e) {
            localIP = "127.0.0.1";
        }
        return localIP;
    }

    private static boolean writeToFile()  {
        FileWriter writer = null;
        try {
            if ( !lock.tryLock(100, TimeUnit.MILLISECONDS) ) {
                return false;
            }

            StringBuffer sb = new StringBuffer();
            int count = 0;
            while(true) {
                String msg = logQueue.poll();
                if ( msg == null ) {
                    break;
                }

                sb.append(msg);
                sb.append("\n");

                count++;
                if ( count >= QUEUE_MAX_SIZE ) {
                    break;
                }
            }

            writer = new FileWriter(getCurrentFilename(), true);
            writer.write(sb.toString());
            writer.flush();
            return true;
        } catch (Exception e) {
        } finally {
            try {
                if ( writer != null ) {
                    writer.close();
                }
            } catch(Exception e) {
            }
            lock.unlock();
        }

        return false;
    }

    private static String getCurrentFilename()  {
        String filename = LOG_PATH + "/" + LOG_NAME;
        return filename;
    }

    static class AlarmData {
        public String project;
        public String module;
        public String code;
        public String message;
        public String server_ip;
        public String client_ip;
        public String script;
        public String time;
        public String env;
    }

    public static void main(String[] args)   {
        long start = System.currentTimeMillis();
        for(int i=0; i< 5000; i++) {
            send("qalarm", "test", "0", i+"GET /api/v1/nearbyOrder/bookorder_list?page_index=1&page_size=20&start_flag=1&end_flag=0&start_gps_longitute=121.517926&start_gps_latitude=31.244884&end_gps_longitute=0.0&end_gps_latitude=0.0&start_lat=0.0&start_lng=0.0&end_lat=0.0&end_lng=0.0 HTTP/1.1\" \"200\" \"4772\" \"-\" \"Dalvik/1.6.0 (Linux; U; Android 4.4.2; CHM-TL00H Build/HonorCHM-TL00H)\" \"116.226.209.162\" \"0.389\"");
        }
        System.out.println(System.currentTimeMillis()-start);
    }
}
