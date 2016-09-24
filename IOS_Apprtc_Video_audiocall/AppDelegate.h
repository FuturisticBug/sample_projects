//
//  AppDelegate.h
//  Connex-New
//
//  Created by Saptarshi's  on 6/24/16.
//  Copyright © 2016 esolz. All rights reserved.
//

#import <UIKit/UIKit.h>
#import <CoreData/CoreData.h>
#include <AudioToolbox/AudioToolbox.h>
#import <AVFoundation/AVFoundation.h>
#import <Contacts/Contacts.h>
#import <PushKit/PushKit.h>
#import <CoreTelephony/CTCallCenter.h>

typedef void(^completionBlock)(BOOL);

@interface AppDelegate : UIResponder <UIApplicationDelegate,PKPushRegistryDelegate>
{
    UINavigationController *navigation;
}

@property (strong, nonatomic) UIWindow *window;

@property (readonly, strong, nonatomic) NSManagedObjectContext *managedObjectContext;
@property (readonly, strong, nonatomic) NSManagedObjectModel *managedObjectModel;
@property (readonly, strong, nonatomic) NSPersistentStoreCoordinator *persistentStoreCoordinator;

- (void)saveContext;
- (NSURL *)applicationDocumentsDirectory;

@property(retain, nonatomic)NSDateFormatter *dateFormatter;
@property (assign, nonatomic) CGSize deviceSize;
@property (retain, nonatomic)UIView *incomingCallView;
@property (retain, nonatomic)UIView *outgoingCallView;
@property (retain, nonatomic)UIView *conversationView;
@property (retain, nonatomic) UIView *thirdCallView;
@property (retain, nonatomic)NSTimer *callTimer;
@property (retain, nonatomic)UILabel *timeLBL;
@property (retain, nonatomic)UIButton *videoBTN,*audioBTN,*endBTN;
@property (retain,nonatomic) NSString *userPhoneNumber;
@property (retain, nonatomic) AVAudioPlayer *audioPlayer;
@property (retain, nonatomic) AVAudioSession *audioSession;
@property (assign, nonatomic) BOOL isUpdateNotificationReceived;
@property (retain ,nonatomic)NSString *callerNumber;

@property (retain, nonatomic) NSString *endedMeetingID,*callerName;

//third call variables
@property (retain, nonatomic) NSString *thirdCallerName;
@property (retain, nonatomic) NSDictionary *thirdCallDic;
//

-(void)syncContact:(completionBlock)comBlock;
-(void)fetchContact;
//-(void)testAlert:(id)message;
@end
